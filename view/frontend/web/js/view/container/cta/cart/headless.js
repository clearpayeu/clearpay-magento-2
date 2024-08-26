(function (d, w, s) {
    let query = `mutation {
                    getClearpayConfigCart(input: {
                        cart_id: "${clearpayCartId}"
                        store_id: "${clearpayStoreId}"
                    }) {
                        allowed_currencies
                        is_enabled
                        mpid
                        is_enabled_cta_cart_page_headless
                        show_lover_limit
                        is_product_allowed
                        is_cbt_enabled
                        placement_after_selector
                        price_selector
                    }
                }`;

    let graphqlEndpoint = window.location.origin + '/graphql';
    let configData;
    let lastKnownPrice = null;

    function fetchConfigData() {
        const requestOptions = {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({query})
        };

        return fetch(graphqlEndpoint, requestOptions)
            .then(response => response.json())
            .then(data => {
                if (data) {
                    const clearpayConfig = data.data.getClearpayConfigCart;
                    let isEnabledCtaMinicart = clearpayConfig.is_enabled_cta_cart_page_headless;

                    if (clearpayConfig.is_enabled && isEnabledCtaMinicart) {
                        let dataMPID = clearpayConfig.mpid,
                            dataShowLowerLimit = clearpayConfig.show_lover_limit,
                            dataPlatform = 'Magento',
                            dataPageType = 'cart',
                            dataIsEligible = clearpayConfig.is_product_allowed,
                            dataCbtEnabledString = Boolean(clearpayConfig.is_cbt_enabled).toString(),
                            squarePlacementId = 'clearpay-cta-cart',
                            widgetContainer = clearpayConfig.placement_after_selector,
                            priceWrapper = clearpayConfig.price_selector;

                        return {
                            dataShowLowerLimit: dataShowLowerLimit,
                            dataCurrency: clearpayCurrency,
                            dataLocale: clearpayLocale,
                            dataIsEligible: dataIsEligible,
                            dataMPID: dataMPID,
                            dataCbtEnabledString: dataCbtEnabledString,
                            dataPlatform: dataPlatform,
                            dataPageType: dataPageType,
                            widgetContainer: widgetContainer,
                            squarePlacementId: squarePlacementId,
                            priceWrapper: priceWrapper
                        };
                    } else {
                        return null;
                    }
                } else {
                    return null;
                }
            })
            .catch(error => {
                console.error("Error:", error);
                throw error;
            });
    }

    function processClearpay() {
        if (configData && configData.priceWrapper) {
            if (document.querySelector(configData.priceWrapper)) {
                updateWidgetInstance();
                lastKnownPrice = getPriceWithoutCurrency(configData.priceWrapper);
                setInterval(checkCartUpdated, 1000);
            }
        }
    }

    function updateWidgetInstance() {
        const priceWrapper = configData.priceWrapper;
        const priceSelectorElement = document.querySelector(priceWrapper);

        if (!priceSelectorElement) {
            return;
        }

        const squarePlacementSelector = document.getElementById(configData.squarePlacementId);
        if (squarePlacementSelector) {
            squarePlacementSelector.outerHTML = ""; // Remove old widget and add a new one
        }

        let priceAmount = getPriceWithoutCurrency(priceWrapper);

        updateClearpayAmount(priceAmount);
    }

    function checkCartUpdated() {
        const currentPrice = getPriceWithoutCurrency(configData.priceWrapper);
        if (currentPrice && currentPrice !== lastKnownPrice) {
            lastKnownPrice = currentPrice;
            updateWidgetInstance();
        }
    }

    // Get price without currency symbol
    function getPriceWithoutCurrency(selector) {
        const element = document.querySelector(selector);

        if (element) {
            let priceText = element.innerText.trim(),
                price = priceText.replace(/[^\d.]/g, '');
            return parseFloat(price);
        } else {
            return null;
        }
    }

    // Add the widget
    function updateClearpayAmount(amount) {
        let wrapperHtml = document.querySelector(configData.widgetContainer),
            dataCurrency = configData?.dataCurrency ? configData.dataCurrency : window.clearpayCurrency;

        const blockHtml = '<square-placement id="' + configData.squarePlacementId + '"' +
            'data-show-lower-limit="' + configData.dataShowLowerLimit + '"' +
            'data-currency="' + dataCurrency + '"' +
            'data-locale="' + configData.dataLocale + '"' +
            'data-is-eligible="' + configData.dataIsEligible + '"' +
            'data-amount="' + amount + '"' +
            'data-mpid="' + configData.dataMPID + '"' +
            'data-cbt-enabled="' + configData.dataCbtEnabledString + '"' +
            'data-platform="' + configData.dataPlatform + '"' +
            'data-page-type="' + configData.dataPageType + '"></square-placement>';

        if (wrapperHtml) {
            wrapperHtml.insertAdjacentHTML('afterend', blockHtml);
        }
    }

    window.addEventListener("load", (event) => {
        if (clearpayCartId !== "") {
            fetchConfigData()
                .then(theConfig => {
                    configData = theConfig;
                    if (configData && configData.priceWrapper) {
                        processClearpay();
                    }
                })
                .catch(error => console.error("Error: ", error));
        }
    });

})(document, window, 'script');
