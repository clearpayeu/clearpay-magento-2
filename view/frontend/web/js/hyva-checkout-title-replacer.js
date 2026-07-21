/**
 * Clearpay - Hyva Checkout Integration
 *
 * Fetches Clearpay config via GraphQL, then imperatively builds the UI:
 *  - Replaces the payment method title label with a Square logo placement.
 *  - Injects the payment-schedule widget and terms copy into clearpay.phtml's
 *    plain-HTML placeholders (no Alpine directives — avoids CSP unsafe-eval).
 *  - Hides the default "Place Order" button while Clearpay is selected.
 *  - Initialises Square.Marketplace for the redirect checkout flow.
 *
 * Global state used for cross-instance persistence (Magewire re-inits):
 *   window._clearpayCache    – fetched data, survives component re-creation.
 *   window._clearpayFetching – prevents duplicate in-flight GraphQL requests.
 *   window._clearpayDataHandler – single live listener for clearpay:data-ready.
 *
 * Order total is re-fetched when shipping changes or the payment step loads so
 * Square placement widgets stay in sync with the updated grand total.
 */
(function () {
    'use strict';

    // ─── Component factory ────────────────────────────────────────────────────
    // Used only for data-fetching and the Square redirect checkout flow.
    // NOT registered with Alpine.data — clearpay.phtml has no Alpine directives.

    window.clearpayComponent = function () {
        return {
            mpid: '',
            currency: '',
            orderTotal: '0.00',
            countryCode: 'UK',
            termsConditionsUrl: '',
            dataReady: false,
            trigger: 'clearpay-hyva-checkout',

            init() {
                const applyData = (data) => {
                    this.mpid               = data.mpid;
                    this.currency           = data.currency;
                    this.orderTotal         = data.orderTotal;
                    this.countryCode        = data.countryCode || 'UK';
                    this.termsConditionsUrl = data.termsConditionsUrl || '';
                    this.dataReady          = true;
                    this.initClearpay();
                };

                // Data already loaded by a previous instance — restore immediately.
                // Also re-dispatch so bootstrap listeners rebuild title logo and content.
                if (window._clearpayCache?.mpid) {
                    applyData(window._clearpayCache);
                    window.dispatchEvent(new CustomEvent('clearpay:data-ready', {detail: window._clearpayCache}));
                    return;
                }

                // Replace any stale listener from a dead previous instance so we
                // never accumulate handlers and always notify the CURRENT instance.
                if (window._clearpayDataHandler) {
                    window.removeEventListener('clearpay:data-ready', window._clearpayDataHandler);
                }
                window._clearpayDataHandler = (e) => applyData(e.detail);
                window.addEventListener('clearpay:data-ready', window._clearpayDataHandler, {once: true});

                // Only one global fetch in flight at a time.
                if (!window._clearpayFetching) {
                    window._clearpayFetching = true;
                    this.fetchMpid();
                    this.fetchOrderTotal();
                    this.setTermsConditionsUrl();
                }
            },

            checkDataReady() {
                if (this.mpid && this.currency && this.orderTotal && !this.dataReady) {
                    this.dataReady = true;
                    const data = {
                        mpid:               this.mpid,
                        currency:           this.currency,
                        orderTotal:         this.orderTotal,
                        countryCode:        this.countryCode,
                        termsConditionsUrl: this.termsConditionsUrl,
                    };
                    window._clearpayCache    = data;
                    window._clearpayFetching = false;
                    window.dispatchEvent(new CustomEvent('clearpay:data-ready', {detail: data}));
                }
            },

            getMageCacheStorage() {
                try {
                    return JSON.parse(localStorage.getItem('mage-cache-storage'));
                } catch (e) {
                    return {};
                }
            },

            setTermsConditionsUrl() {
                this.termsConditionsUrl = this.getTermsLink();
            },

            async fetchMpid() {
                const query = `query { clearpayConfig { mpid } }`;
                try {
                    const response = await this.executeGraphqlQuery(query);
                    if (response?.data?.clearpayConfig?.mpid) {
                        this.mpid = response.data.clearpayConfig.mpid;
                        this.checkDataReady();
                    }
                } catch (e) {
                    // Silently fail
                }
            },

            getCartId() {
                try {
                    const storageData = this.getMageCacheStorage();
                    // Hyva stores the masked guest cart id as cartId or guestMaskId
                    // depending on the version; try both.
                    return storageData?.cart?.cartId
                        || storageData?.cart?.guestMaskId
                        || null;
                } catch (e) {
                    return null;
                }
            },

            async fetchOrderTotal() {
                try {
                    const cartId = this.getCartId();

                    if (cartId) {
                        const query = `
                            query getCart($cartId: String!) {
                                cart(cart_id: $cartId) {
                                    prices { grand_total { value currency } }
                                }
                            }
                        `;
                        const response = await this.executeGraphqlQuery(query, {cartId});
                        if (response?.data?.cart?.prices?.grand_total) {
                            const gt = response.data.cart.prices.grand_total;
                            this.orderTotal = gt.value;
                            this.currency = gt.currency;
                            this.checkDataReady();
                            return;
                        }
                    }

                    // Fallback: customerCart works for logged-in users without
                    // needing a cart_id (guests without cartId in localStorage
                    // will get a GraphQL error and we silently skip it).
                    const fallback = await this.executeGraphqlQuery(
                        `{ customerCart { prices { grand_total { value currency } } } }`
                    );
                    if (fallback?.data?.customerCart?.prices?.grand_total) {
                        const gt = fallback.data.customerCart.prices.grand_total;
                        this.orderTotal = gt.value;
                        this.currency = gt.currency;
                        this.checkDataReady();
                    }
                } catch (e) {}
            },

            initClearpay() {
                if (!this.mpid) return;
                Square.Marketplace.initializeForRedirect({
                    countryCode: this.countryCode.toUpperCase(),
                    buyNow: true,
                    pickup: false,
                    target: '#' + this.trigger,
                    onCommenceCheckout: actions => this.getClearpayToken(actions),
                });
            },

            async getClearpayToken(actions) {
                const cartId = this.getCartId(),
                    confirmPath = 'clearpay/payment/capture',
                    cancelPath  = 'clearpay/payment/capture';

                try {
                    if (!cartId || typeof hyvaCheckout === 'undefined') {
                        throw new Error('Unable to initialize the Clearpay checkout.');
                    }

                    // The Square button bypasses Hyvä's regular place-order button.
                    // Flush pending Magewire form changes and run the same client-side
                    // validation preflight before creating the redirect checkout.
                    await hyvaCheckout.navigation.executeTasks();
                    const isValid = await hyvaCheckout.validation.validate();
                    await hyvaCheckout.isIdle();

                    if (isValid === false) {
                        Square.Marketplace.close();
                        actions.reject(Square.Marketplace.constants.SERVICE_UNAVAILABLE);
                        return;
                    }

                    const mutation = `
                        mutation CreateClearpayCheckout($cartId: String!, $cancelPath: String!, $confirmPath: String!) {
                            createClearpayCheckout(input: {
                                cart_id: $cartId
                                redirect_path: { cancel_path: $cancelPath confirm_path: $confirmPath }
                            }) {
                                clearpay_token
                                clearpay_redirectCheckoutUrl
                            }
                        }
                    `;
                    const response = await this.executeGraphqlQuery(
                        mutation,
                        {cartId, cancelPath, confirmPath}
                    );
                    const checkout = response?.data?.createClearpayCheckout;

                    if (checkout?.clearpay_token && checkout?.clearpay_redirectCheckoutUrl) {
                        window.location.href = checkout.clearpay_redirectCheckoutUrl;
                        return;
                    }

                    throw new Error('Clearpay did not return a checkout token.');
                } catch (error) {
                    Square.Marketplace.close();
                    actions.reject(Square.Marketplace.constants.SERVICE_UNAVAILABLE);
                }
            },

            async executeGraphqlQuery(query, variables = {}) {
                const graphqlEndpoint = `${window.location.origin}/graphql`;
                const body = JSON.stringify({query, variables});
                const storageData = this.getMageCacheStorage();
                const storeViewCode = storageData?.cart?.storeViewCode;
                const customerToken = storageData?.customer?.signin_token;

                const headers = {'Content-Type': 'application/json', 'Store': storeViewCode};
                if (customerToken) {
                    headers['Authorization'] = `Bearer ${customerToken}`;
                }

                const response = await fetch(graphqlEndpoint, {method: 'POST', headers, body});
                if (!response.ok) throw new Error(`GraphQL query failed: ${response.statusText}`);
                return response.json();
            },

            getTermsText() {
                const template = document.getElementById('clearpay-hyva-template');
                return template?.dataset?.termsDefault || '';
            },

            getTermsLink() {
                return 'https://www.clearpay.co.uk/en-GB/terms-of-service';
            },
        };
    };

    // ─── Payment method selection ─────────────────────────────────────────────
    // Hides the default "Place Order" button while Clearpay is the active method.
    // currentPaymentMethod persists across Magewire re-renders so we can re-apply
    // after the button is replaced in the DOM.

    let currentPaymentMethod = '';

    function applyOrderButtonVisibility() {
        const orderBtn = document.querySelector('.btn-place-order');
        if (orderBtn) {
            orderBtn.classList.toggle('hidden', currentPaymentMethod === 'clearpay');
        }
    }

    function onPaymentMethodSelect(methodCode) {
        currentPaymentMethod = methodCode;
        applyOrderButtonVisibility();
    }

    // ─── Payment content injection ────────────────────────────────────────────
    // Builds the payment-schedule widget and terms text into clearpay.phtml's
    // plain-HTML placeholder elements. Re-creates square-placement on each call
    // so amount updates after shipping/total changes are reflected.

    function upsertSquarePlacement(container, data, type) {
        if (!container || !data?.mpid || !data?.currency || data.orderTotal == null || data.orderTotal === '') {
            return;
        }

        container.querySelectorAll('square-placement').forEach((el) => el.remove());

        const sp = document.createElement('square-placement');
        sp.setAttribute('data-mpid', data.mpid);
        sp.setAttribute('data-amount', data.orderTotal);
        sp.setAttribute('data-currency', data.currency);
        sp.setAttribute('data-type', type);
        sp.setAttribute('data-platform', 'Magento');
        sp.setAttribute('data-page-type', 'checkout');
        container.appendChild(sp);
    }

    function injectPaymentContent(data) {
        if (!data.mpid || !data.currency || data.orderTotal == null || data.orderTotal === '') return;

        upsertSquarePlacement(
            document.getElementById('clearpay-payment-schedule-container'),
            data,
            'payment-schedule'
        );

        const termsTextEl = document.getElementById('clearpay-terms-text');
        if (termsTextEl && !termsTextEl.textContent.trim()) {
            const template = document.getElementById('clearpay-hyva-template');
            termsTextEl.textContent = template?.dataset?.termsDefault || '';
        }

        const termsLinkEl = document.getElementById('clearpay-terms-link');
        if (termsLinkEl) {
            termsLinkEl.href = data.termsConditionsUrl || 'https://www.clearpay.co.uk/en-GB/terms-of-service';
        }

        const termsContainerEl = document.getElementById('clearpay-terms-container');
        if (termsContainerEl) termsContainerEl.style.display = '';

        // Clearpay is UK-only — no conditional button colour class needed.
    }

    // ─── Title replacer ───────────────────────────────────────────────────────

    let clearpayComponentInstance = null;

    function replaceClearpayTitle() {
        const clearpayLabel = document.querySelector('label[for="payment-method-clearpay"]');
        let clearpaySpan = null;

        if (!clearpayLabel) {
            clearpaySpan = document.querySelector('#payment-method-option-clearpay > label > span');
            if (!clearpaySpan) return;
        }

        let titleDiv = null;
        if (!clearpayLabel && clearpaySpan) {
            titleDiv = clearpaySpan;
        } else {
            titleDiv = clearpayLabel.querySelector('.text-gray-700.font-bold');
        }

        if (!titleDiv) return;
        if (titleDiv.querySelector('.clearpay-logo-placement')) return;

        const placementWrapper = document.createElement('div');
        placementWrapper.className = 'clearpay-logo-placement';

        const loadingDiv = document.createElement('div');
        loadingDiv.className = 'text-sm text-gray-500';
        loadingDiv.textContent = 'Loading...';
        loadingDiv.id = 'clearpay-logo-loading';
        placementWrapper.appendChild(loadingDiv);

        titleDiv.textContent = '';
        titleDiv.appendChild(placementWrapper);

        clearpayComponentInstance = window.clearpayComponent();
        clearpayComponentInstance.init();
    }

    function injectSquarePlacement(data) {
        const placementWrapper = document.querySelector('.clearpay-logo-placement');
        if (!placementWrapper) return;

        const loadingDiv = document.getElementById('clearpay-logo-loading');
        if (loadingDiv) loadingDiv.remove();

        upsertSquarePlacement(placementWrapper, data, 'logo');
    }

    // ─── Order total refresh ──────────────────────────────────────────────────
    // Re-fetches grand total after shipping changes and re-renders widgets.

    let refreshOrderTotalTimeout = null;
    let refreshOrderTotalInFlight = null;

    function scheduleOrderTotalRefresh(delayMs = 350) {
        clearTimeout(refreshOrderTotalTimeout);
        refreshOrderTotalTimeout = setTimeout(() => {
            if (!refreshOrderTotalInFlight) {
                refreshOrderTotalInFlight = refreshCachedOrderTotal().finally(() => {
                    refreshOrderTotalInFlight = null;
                });
            }
        }, delayMs);
    }

    async function refreshCachedOrderTotal() {
        if (!window._clearpayCache?.mpid) return;

        const component = clearpayComponentInstance || window.clearpayComponent();
        component.mpid = window._clearpayCache.mpid;
        component.currency = window._clearpayCache.currency;
        component.countryCode = window._clearpayCache.countryCode || 'UK';
        component.termsConditionsUrl = window._clearpayCache.termsConditionsUrl || component.getTermsLink();

        await component.fetchOrderTotal();

        if (!component.orderTotal || !component.currency) return;

        window._clearpayCache = Object.assign({}, window._clearpayCache, {
            orderTotal: component.orderTotal,
            currency: component.currency,
        });

        if (clearpayComponentInstance) {
            clearpayComponentInstance.orderTotal = component.orderTotal;
            clearpayComponentInstance.currency = component.currency;
        }

        window.dispatchEvent(new CustomEvent('clearpay:data-ready', {detail: window._clearpayCache}));
    }

    // ─── Bootstrap ────────────────────────────────────────────────────────────

    function init() {
        // When Clearpay data arrives (first fetch or cached re-init), build both
        // the title logo and the payment-schedule / terms content.
        window.addEventListener('clearpay:data-ready', (event) => {
            if (!event.detail) return;
            injectSquarePlacement(event.detail);
            injectPaymentContent(event.detail);
        });

        // Polling fallback: catches the case where the title replacer creates its
        // own instance that fetches data without going through the event bus.
        let checkCount = 0;
        const checkDataInterval = setInterval(() => {
            checkCount++;
            if (clearpayComponentInstance && clearpayComponentInstance.dataReady) {
                clearInterval(checkDataInterval);
                const data = {
                    mpid:               clearpayComponentInstance.mpid,
                    currency:           clearpayComponentInstance.currency,
                    orderTotal:         clearpayComponentInstance.orderTotal,
                    countryCode:        clearpayComponentInstance.countryCode,
                    termsConditionsUrl: clearpayComponentInstance.termsConditionsUrl,
                };
                injectSquarePlacement(data);
                injectPaymentContent(data);
            } else if (checkCount > 50) {
                clearInterval(checkDataInterval);
            }
        }, 200);

        replaceClearpayTitle();

        // Detect the initially-selected payment method. Clearpay being pre-selected
        // is most reliably detected by its template div being in the DOM. We retry
        // several times because Magewire may render the Place Order button after
        // DOMContentLoaded, and subsequent Magewire updates may un-hide it.
        function detectInitialMethod() {
            if (document.getElementById('clearpay-hyva-template')) {
                onPaymentMethodSelect('clearpay');
            } else {
                const checked = document.querySelector('input[name="payment-method-option"]:checked');
                if (checked?.value) onPaymentMethodSelect(checked.value);
            }
        }
        [0, 300, 700, 1500].forEach(ms => setTimeout(detectInitialMethod, ms));

        // Re-apply button visibility after every Magewire server round-trip —
        // Magewire morphs the DOM and can restore the button without our class.
        const reapplyAfterMagewire = () => applyOrderButtonVisibility();
        window.addEventListener('livewire:message.processed', reapplyAfterMagewire);
        window.addEventListener('message.processed', reapplyAfterMagewire);

        window.addEventListener('checkout:step:loaded', (event) => {
            if (event.detail?.route === 'payment') {
                scheduleOrderTotalRefresh();
            }
            replaceClearpayTitle();
            detectInitialMethod();
        });

        window.addEventListener('checkout:shipping:method-activate', scheduleOrderTotalRefresh);
        window.addEventListener('shipping:method:update', scheduleOrderTotalRefresh);

        window.addEventListener('checkout:payment:method-activate', (event) => {
            if (!event.detail?.method) return;
            onPaymentMethodSelect(event.detail.method);
            setTimeout(replaceClearpayTitle, 100);
            if (event.detail.method === 'clearpay') {
                scheduleOrderTotalRefresh();
            }
        });

        window.addEventListener('checkout:payment:method-list-updated', () => {
            setTimeout(replaceClearpayTitle, 100);
        });

        const observer = new MutationObserver((mutations) => {
            for (const mutation of mutations) {
                if (!mutation.addedNodes.length) continue;
                replaceClearpayTitle();
                for (const node of mutation.addedNodes) {
                    if (node.nodeType !== Node.ELEMENT_NODE) continue;
                    const clearpayTemplate = node.id === 'clearpay-hyva-template'
                        ? node
                        : node.querySelector?.('#clearpay-hyva-template');
                    if (clearpayTemplate) {
                        // Clearpay template just appeared — it is now the selected method.
                        onPaymentMethodSelect('clearpay');
                        scheduleOrderTotalRefresh();
                        break;
                    }
                }
            }
        });

        const paymentMethodsList = document.getElementById('payment-method-list');
        if (paymentMethodsList) {
            observer.observe(paymentMethodsList, {childList: true, subtree: true});
        }

        // Broader observer: watches the whole document for .btn-place-order being
        // added or having its class reset by Magewire, and immediately re-hides it
        // when Clearpay is the active method.
        const btnObserver = new MutationObserver((mutations) => {
            if (currentPaymentMethod !== 'clearpay') return;
            for (const mutation of mutations) {
                // Node added — check if it is or contains the button.
                for (const node of mutation.addedNodes) {
                    if (node.nodeType !== Node.ELEMENT_NODE) continue;
                    const btn = node.classList?.contains('btn-place-order')
                        ? node
                        : node.querySelector?.('.btn-place-order');
                    if (btn) { btn.classList.add('hidden'); }
                }
                // Attribute change — button's class was reset externally.
                if (
                    mutation.type === 'attributes' &&
                    mutation.target.classList?.contains('btn-place-order') &&
                    !mutation.target.classList.contains('hidden')
                ) {
                    mutation.target.classList.add('hidden');
                }
            }
        });
        btnObserver.observe(document.body, {
            childList: true,
            subtree: true,
            attributes: true,
            attributeFilter: ['class'],
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
