window.addEventListener("load", () => {
    const initExpressCheckout = () => {
        return {
            countryCode: window?.clearpayLocaleCode ? window.clearpayLocaleCode : "US",
            enableForMinicart: false,
            isLoading: true,
            trigger: "clearpay-button-minicart",
            minPrice: 0,
            maxPrice: 1000,
            shippingOptionRequired: true,
            isProductAllowed: false,
            clearpayCartSubtotal: 0,
            ecButtonPlace: document.querySelector("#minicart-content-wrapper .subtotal"),
            configData: '',
            clearpayButton: document.querySelector('#headless-clearpay-minicart-ec'),
            initClearpayAction: false,
            isVirtual: false,

            init() {
                document.addEventListener('showHeadlessMinicart', (event) => {
                    this.extractSectionData(event.detail.clearpayConfig);
                    this.configData = event.detail.clearpayConfig;
                });
            },

            extractSectionData(data, observer = false) {
                this.isLoading = false;

                this.ecButtonPlace = data?.placement_after_selector
                    ? document.querySelector(data.placement_after_selector)
                    : this.ecButtonPlace;

                if(!observer) {
                    this.trackPriceChanges(document.querySelector(data.placement_wrapper));
                }
        
                if (data) {
                    this.setCurrentData(data);
                }

                if (this.ecButtonPlace) {
                    if (document.querySelector('#clearpay-cta-mini-cart')) {
                        this.ecButtonPlace = document.querySelector('#clearpay-cta-mini-cart');
                    }

                    this.ecButtonPlace.insertAdjacentElement('afterend', this.clearpayButton);

                    if(!this.initClearpayAction) {
                        this.initClearpay();
                    }

                    // Add click event listener to the button
                    if (this.clearpayButton) {
                        this.clearpayButton.addEventListener('click', (event) => this.ecValidationAddToCart(event));
                    }

                    if(document.querySelector(".product-info-main #product-addtocart-button")){
                        document.querySelector(".product-info-main #product-addtocart-button").addEventListener('click', (event) => 
                            this.trackPriceChanges(document.querySelector(this.configData.placement_after_selector))
                        );
                    }

                    this.validateShowButton(this.checkPriceLimit(this.clearpayCartSubtotal));
                }
            },

            setCurrentData (data) {
                this.enableForMinicart = (data.is_enabled && data.is_enabled_ec_minicart_headless) ?? this.enableForPDP;
                this.isProductAllowed = data.is_product_allowed ?? this.isProductAllowed;
                this.clearpayCartSubtotal = this.checkCurrentSubtotal();
                this.minPrice = data.min_amount ? +data.min_amount : this.minPrice;
                this.maxPrice = data.max_amount ? +data.max_amount : this.maxPrice;
                this.isVirtual = data.is_virtual ? data.is_virtual : this.isVirtual;
                this.shippingOptionRequired = !this.isVirtual;
                window.miniCartHasVirtual = this.isVirtual;
            },

            reloadMinicart () {
                const url = '/customer/section/load/';
                fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: {sections: "cart"}
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .catch(error => {
                    console.error('Failed to refresh cart section:', error);
                });
            },

            checkCurrentSubtotal () {
                let currentCartData = JSON.parse(localStorage.getItem("mage-cache-storage")).cart;

                if(currentCartData && currentCartData?.subtotalAmount) {
                    return +currentCartData?.subtotalAmount;
                }

                return 0;
            },

            trackPriceChanges(element) {
                if(!element) return;

                if (document.querySelector(this.configData.placement_after_selector)) {
                    this.extractSectionData(this.configData, true);
                    return;
                }

                const targetNode = element,
                callback = (mutationsList, observer) => {
                    for (const mutation of mutationsList) {
                        mutation.addedNodes.forEach(node => {
                            if (node?.classList?.value == 'price' && document.querySelector(this.configData.placement_after_selector)) {
                                document.dispatchEvent(window.reloadMinicart);
                                // observer.disconnect();
                                return;
                            }
                        });
                    }
                };

                const observer = new MutationObserver(callback),
                    config = { 
                        characterData: true, 
                        childList: true, 
                        subtree: true 
                    };

                observer.observe(targetNode, config);
            },

            validateShowButton(priceIsValid = false) {
                if (this.enableForMinicart && this.isProductAllowed && priceIsValid) {
                    this.clearpayButton.classList.remove("hidden");
                } else {
                    this.clearpayButton.classList.add("hidden");
                }
            },

            ecValidationAddToCart(event) {
                document.getElementById(this.trigger).click();
                this.initClearpay();
            },

            checkPriceLimit(cartSubtotal) {
                let total = cartSubtotal?.subtotalAmount ? cartSubtotal?.subtotalAmount : cartSubtotal;

                return +total >= this.minPrice && +total <= this.maxPrice;
            },

            objectToUrlEncoded(obj) {
                return new URLSearchParams(obj).toString();
            },

            getShippingOptions(shippingAddress, actions) {
                shippingAddress = this.objectToUrlEncoded(shippingAddress);

                this.fetchData("clearpay/express/getShippingOptions", shippingAddress)
                    .then(response => {
                        if (response?.shippingOptions) {
                            return actions.resolve(response.shippingOptions);
                        } else {
                            AfterPay.close();
                            return actions.reject(Square.Marketplace.constants.SHIPPING_ADDRESS_UNSUPPORTED);
                        }
                    });
            },

            onComplete(event) {
                if (event.data.status === 'CANCELLED') {
                    localStorage?.removeItem('mage-cache-storage');
                    window.location.reload();
                }

                this.placeOrder(event);
            },

            handleMessage(type, text) {
                if (typeof (dispatchMessages) != "undefined") {
                    dispatchMessages([{type, text}], 5000);
                }
            },

            placeOrder(event) {
                const data = this.objectToUrlEncoded(event.data);

                this.isLoading = true;

                this.fetchData("clearpay/express/placeOrder", data)
                    .then(response => {
                        if(response?.error) {
                            let messages = [
                                {
                                    text: response?.error,
                                    type: 'error'
                                }
                            ],
                            messagesJson = JSON.stringify(messages);

                            cookieStore.set('mage-messages', messagesJson);
                            window.location.href = response.redirectUrl;
                        }else{
                            if (response?.redirectUrl) {
                                localStorage?.removeItem('mage-cache-storage');
                                localStorage?.removeItem('messages');
                                window.mageMessages = [];
                                window.location.href = response.redirectUrl;
                                this.isLoading = false;
                            }
                        }
                    });
            },

            getClearpayToken(actions) {
                this.fetchData("clearpay/express/createCheckout")
                    .then(response => {
                        if (response?.clearpay_token) {
                            return actions.resolve(response.clearpay_token);
                        } else {
                            AfterPay.close();
                            return actions.reject(Square.Marketplace.constants.SERVICE_UNAVAILABLE);
                        }
                    });
            },

            fetchData(url = "", data = "") {
                const postUrl = `${BASE_URL}${url}`;

                this.isLoading = true;

                return window.fetch(postUrl, {
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: data,
                    method: 'POST',
                    dataType: 'json'
                })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok ' + response.statusText);
                        }
                        return response.json();
                    })
                    .then(data => {
                        return data;
                    })
                    .catch(error => {
                        console.error('There was a problem with the fetch operation:', error);
                    });
            },

            checkProductInCart () {
                let cartItems = JSON.parse(localStorage.getItem("mage-cache-storage"))?.cart?.items,
                    hasVirtual = false,
                    hasSimple = false;

                if(cartItems?.length > 0) {
                    cartItems.forEach((item, index) => {
                        if(item.product_type == "virtual" || item.product_type == "downloadable") {
                            hasVirtual = true;
                        }else {
                            hasSimple = true; 
                        }
                    });
                }

                if(hasVirtual && hasSimple) {
                    this.shippingOptionRequired = true;
                }

                if(hasVirtual && hasSimple == false) {
                    this.shippingOptionRequired = false;
                }

                if(hasVirtual == false && hasSimple) {
                    this.shippingOptionRequired = true;
                }
            },

            initClearpay() {
                this.checkProductInCart();

                AfterPay.initializeForPopup({
                    countryCode: this.countryCode.toLocaleUpperCase(),
                    buyNow: true,
                    shippingOptionRequired: this.shippingOptionRequired,
                    pickup: false,
                    target: "#" + this.trigger,
                    onCommenceCheckout: actions => this.getClearpayToken(actions),
                    onComplete: event => this.onComplete(event),
                    onShippingAddressChange: (shippingAddress, actions) => this.getShippingOptions(shippingAddress, actions)
                });
                this.initClearpayAction = true;
            }
        };
    };

    window.expressCheckout = initExpressCheckout();
    window.expressCheckout.init();
});