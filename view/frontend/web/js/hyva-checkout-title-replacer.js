/**
 * Clearpay - Hyva Checkout Title Replacer
 * Replaces the Clearpay payment method title with Square Placement logo
 */
(function () {
    'use strict';

    /**
     * Main Clearpay component
     */
    window.clearpayComponent = function () {
        return {
            isVisible: true,
            currentMethod: '',
            currency: '',
            trigger: 'clearpay-hyva-checkout',
            mpid: '',
            countryCode: 'UK',
            orderTotal: '0.00',
            shippingAddress: '',
            termsConditionsUrl: '',
            platform: 'Magento',
            pageType: 'checkout',
            placeOrderBtn: '.btn-place-order',
            redirectCheckoutUrl: '',
            dataReady: false,

            /**
             * Initialize the component (called by Alpine.js)
             */
            init() {
                this.fetchMpid();
                this.fetchOrderTotal();
                this.initClearpay();
                this.setTermsConditionsUrl();
            },

            /**
             * Check if all required data is loaded and dispatch event
             */
            checkDataReady() {
                if (this.mpid && this.currency && this.orderTotal && !this.dataReady) {
                    this.dataReady = true;

                    // Inject the Square placement directly
                    this.injectSquarePlacement();

                    // Also dispatch event for compatibility
                    window.dispatchEvent(new CustomEvent('clearpay:data-ready', {
                        detail: {
                            mpid: this.mpid,
                            currency: this.currency,
                            orderTotal: this.orderTotal
                        }
                    }));
                }
            },

            /**
             * Inject Square placement logo
             */
            injectSquarePlacement() {
                const placementWrapper = document.querySelector('.clearpay-logo-placement');
                if (!placementWrapper) {
                    return;
                }

                // Check if already injected
                if (placementWrapper.querySelector('square-placement')) {
                    return;
                }

                // Remove loading text
                const loadingDiv = document.getElementById('clearpay-logo-loading');
                if (loadingDiv) {
                    loadingDiv.remove();
                }

                // Create square placement element
                const squarePlacement = document.createElement('square-placement');
                squarePlacement.setAttribute('data-mpid', this.mpid);
                squarePlacement.setAttribute('data-amount', this.orderTotal);
                squarePlacement.setAttribute('data-currency', this.currency);
                squarePlacement.setAttribute('data-platform', 'Magento');
                squarePlacement.setAttribute('data-type', 'logo');
                squarePlacement.setAttribute('data-page-type', 'checkout');

                placementWrapper.appendChild(squarePlacement);
            },

            /**
             * Handles payment method selection. Stores the selected method and toggles the place order button visibility.
             */
            onPaymentMethodSelect(methodCode) {
                this.currentMethod = methodCode;
                const orderBtn = document.querySelector(this.placeOrderBtn);

                if (orderBtn) {
                    if (methodCode === 'clearpay') {
                        orderBtn.classList.add('hidden');
                    } else {
                        orderBtn.classList.remove('hidden');
                    }
                }
            },

            /**
             * Checks and handles the currently selected payment method on component init.
             */
            checkInitialPaymentMethod() {
                setTimeout(() => {
                    const checked = document.querySelector('input[name=\"payment-method-option\"]:checked');
                    if (checked && checked.value) {
                        this.onPaymentMethodSelect(checked.value);
                    }
                }, 0);
            },

            /**
             * Returns the parsed mage-cache-storage object from localStorage.
             */
            getMageCacheStorage() {
                try {
                    return JSON.parse(localStorage.getItem('mage-cache-storage'));
                } catch (error) {
                    return {};
                }
            },

            /**
             * Sets the Terms & Conditions URL based on the currency.
             */
            setTermsConditionsUrl() {
                this.termsConditionsUrl = this.getTermsLink();
            },

            /**
             * Fetches Clearpay MPID via GraphQL query.
             */
            async fetchMpid() {
                const query = `
                        query {
                            clearpayConfig {
                                mpid
                            }
                        }
                    `;

                try {
                    const response = await this.executeGraphqlQuery(query);
                    if (response?.data?.clearpayConfig?.mpid) {
                        this.mpid = response.data.clearpayConfig.mpid;
                        this.checkDataReady();
                    }
                } catch (error) {
                    // Silently fail
                }
            },

            /**
             * Retrieves the cart ID from mage-cache-storage in localStorage.
             */
            getCartId() {
                try {
                    const storageData = this.getMageCacheStorage();
                    const cartId = storageData?.cart?.cartId;

                    if (!cartId) {
                        throw new Error('Cart ID not found in mage-cache-storage.');
                    }
                    return cartId;
                } catch (error) {
                    return null;
                }
            },

            /**
             * Fetches the order total and currency via GraphQL query.
             */
            fetchOrderTotal() {
                const cartId = this.getCartId();
                if (!cartId) {
                    return;
                }

                const query = `
                        query getCart($cartId: String!) {
                            cart(cart_id: $cartId) {
                                prices {
                                    grand_total {
                                        value
                                        currency
                                    }
                                }
                            }
                        }
                    `;

                this.executeGraphqlQuery(query, {cartId})
                    .then(response => {
                        if (response?.data?.cart?.prices?.grand_total) {
                            const grandTotal = response.data.cart.prices.grand_total;
                            this.orderTotal = grandTotal.value;
                            this.currency = grandTotal.currency;
                            this.checkDataReady();
                        }
                    })
                    .catch(error => {});
            },

            /**
             * Initializes the Clearpay widget with the required parameters.
             */
            initClearpay() {
                if (!this.mpid) {
                    console.warn('MPID is missing. Cannot initialize Clearpay.');
                    return;
                }

                Square.Marketplace.initializeForRedirect({
                    countryCode: this.countryCode.toUpperCase(),
                    buyNow: true,
                    pickup: false,
                    target: "#" + this.trigger,
                    onCommenceCheckout: actions => this.getClearpayToken(actions),
                });
            },

            /**
             * Fetches the Clearpay token for payment (via API).
             */
            getClearpayToken(actions) {
                const cartId = this.getCartId(),
                    confirmPath = 'clearpay/payment/capture',
                    cancelPath = 'clearpay/payment/capture';

                const mutation = `
                        mutation CreateClearpayCheckout($cartId: String!, $cancelPath: String!, $confirmPath: String!) {
                            createClearpayCheckout(input: {
                                cart_id: $cartId
                                redirect_path: {
                                    cancel_path: $cancelPath
                                    confirm_path: $confirmPath
                                }
                            }) {
                                clearpay_token
                                clearpay_redirectCheckoutUrl
                            }
                        }
                    `;

                this.executeGraphqlQuery(mutation, {cartId, cancelPath, confirmPath})
                    .then(response => {
                        if (response?.data.createClearpayCheckout.clearpay_token && response?.data.createClearpayCheckout.clearpay_redirectCheckoutUrl) {
                            window.location.href = response.data.createClearpayCheckout.clearpay_redirectCheckoutUrl;
                        } else {
                            Square.Marketplace.close();
                            actions.reject(Square.Marketplace.constants.SERVICE_UNAVAILABLE);
                        }
                    })
                    .catch(error => {});
            },

            /**
             * Executes a GraphQL query to the Magento server.
             */
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

                const response = await fetch(graphqlEndpoint, {
                    method: 'POST',
                    headers,
                    body,
                });

                if (!response.ok) {
                    throw new Error(`GraphQL query failed: ${response.statusText}`);
                }
                return response.json();
            },

            /**
             * Converts an object to a URL-encoded string (application/x-www-form-urlencoded).
             */
            objectToUrlEncoded(obj) {
                return Object.keys(obj)
                    .map(key => encodeURIComponent(key) + '=' + encodeURIComponent(obj[key]))
                    .join('&');
            },

            /**
             * Returns the terms text depending on the currency.
             */
            getTermsText() {
                return window.clearpayTermsTranslations.DEFAULT;
            },

            /**
             * Returns the Clearpay terms link depending on the currency.
             */
            getTermsLink() {
                return 'https://www.clearpay.co.uk/en-GB/terms-of-service';
            },
        };
    };

    // Store component instance globally so we can access it
    let clearpayComponentInstance = null;

    /**
     * Replace the Clearpay payment method title with Square logo placement
     */
    function replaceClearpayTitle() {
        // Find the Clearpay payment method option
        const clearpayLabel = document.querySelector('label[for="payment-method-clearpay"]');

        if (!clearpayLabel) {
            return;
        }

        // Find the title div within the label
        const titleDiv = clearpayLabel.querySelector('.text-gray-700.font-bold');

        if (!titleDiv) {
            return;
        }

        // Check if we already replaced the title
        if (titleDiv.querySelector('.clearpay-logo-placement')) {
            return;
        }

        // Create a wrapper with Alpine component
        const placementWrapper = document.createElement('div');
        placementWrapper.className = 'clearpay-logo-placement';
        placementWrapper.setAttribute('x-data', 'clearpayComponentInstance = clearpayComponent()');

        // Initially show loading text
        const loadingDiv = document.createElement('div');
        loadingDiv.className = 'text-sm text-gray-500';
        loadingDiv.textContent = 'Loading...';
        loadingDiv.id = 'clearpay-logo-loading';
        placementWrapper.appendChild(loadingDiv);

        // Replace the original title text with our wrapper
        titleDiv.textContent = '';
        titleDiv.appendChild(placementWrapper);

        // Initialize Alpine.js on the wrapper
        if (window.Alpine) {
            window.Alpine.initTree(placementWrapper);
        }
    }

    /**
     * Inject Square placement logo once data is ready
     */
    function injectSquarePlacement(data) {
        const placementWrapper = document.querySelector('.clearpay-logo-placement');
        if (!placementWrapper) {
            return;
        }

        // Check if already injected
        if (placementWrapper.querySelector('square-placement')) {
            return;
        }

        // Remove loading text
        const loadingDiv = document.getElementById('clearpay-logo-loading');
        if (loadingDiv) {
            loadingDiv.remove();
        }

        // Validate data
        if (!data.mpid || !data.currency || !data.orderTotal) {
            return;
        }

        // Create square placement element
        const squarePlacement = document.createElement('square-placement');
        squarePlacement.setAttribute('data-mpid', data.mpid);
        squarePlacement.setAttribute('data-amount', data.orderTotal);
        squarePlacement.setAttribute('data-currency', data.currency);
        squarePlacement.setAttribute('data-platform', 'Magento');
        squarePlacement.setAttribute('data-type', 'logo');
        squarePlacement.setAttribute('data-page-type', 'checkout');

        placementWrapper.appendChild(squarePlacement);
    }

    /**
     * Initialize the title replacement
     */
    function init() {
        // Listen for data ready event
        window.addEventListener('clearpay:data-ready', (event) => {
            if (event.detail) {
                injectSquarePlacement(event.detail);
            }
        });

        // Polling fallback: check if component data is ready
        let checkCount = 0;
        const checkDataInterval = setInterval(() => {
            checkCount++;

            if (clearpayComponentInstance && clearpayComponentInstance.dataReady) {
                clearInterval(checkDataInterval);
                injectSquarePlacement({
                    mpid: clearpayComponentInstance.mpid,
                    currency: clearpayComponentInstance.currency,
                    orderTotal: clearpayComponentInstance.orderTotal
                });
            } else if (checkCount > 50) {
                // Stop checking after 10 seconds (50 * 200ms)
                clearInterval(checkDataInterval);
            }
        }, 200);

        // Try to replace immediately
        replaceClearpayTitle();

        // Also listen for checkout step loaded event
        window.addEventListener('checkout:step:loaded', function () {
            replaceClearpayTitle();
        });

        // Listen for payment method activation
        window.addEventListener('checkout:payment:method-activate', (event) => {
            if (event.detail && event.detail.method) {
                setTimeout(replaceClearpayTitle, 100);
            }
        });

        // Listen for payment method list updates
        window.addEventListener('checkout:payment:method-list-updated', function () {
            setTimeout(replaceClearpayTitle, 100);
        });

        // Use MutationObserver to catch dynamic changes
        const observer = new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
                if (mutation.addedNodes.length) {
                    replaceClearpayTitle();
                }
            });
        });

        // Observe the payment methods container
        const paymentMethodsList = document.getElementById('payment-method-list');
        if (paymentMethodsList) {
            observer.observe(paymentMethodsList, {
                childList: true,
                subtree: true
            });
        }
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();

