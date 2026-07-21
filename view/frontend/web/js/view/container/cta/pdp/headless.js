(function (d, w, s) {
    let query = `mutation {
                    getClearpayConfigPdp(input: {
                        product_sku: "${clearpayProductSku}"
                        store_id: "${clearpayStoreId}"
                    }) {
                        allowed_currencies
                        is_enabled
                        mpid
                        is_enabled_cta_pdp_headless
                        product_type
                        show_lover_limit
                        is_product_allowed
                        is_cbt_enabled
                        placement_after_selector
                        placement_after_selector_bundle
                        price_selector
                        price_selector_bundle
                        placement_id
                    }
                }`;

    let graphqlEndpoint = window.location.origin + '/graphql';

    function fetchConfigData() {
        const requestOptions = {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({query})
        };

        return fetch(graphqlEndpoint, requestOptions)
            .then(response => response.json())
            .then(data => {
                if (data?.errors) {
                    console.error("Error:", data.errors[0].message);
                    return null;
                }
                if (data) {
                    const clearpayConfig = data.data.getClearpayConfigPdp;

                    if (clearpayConfig.is_enabled && clearpayConfig.is_enabled_cta_pdp_headless) {
                        let dataMPID = clearpayConfig.mpid,
                            dataShowLowerLimit = clearpayConfig.show_lover_limit,
                            dataPlatform = 'Magento',
                            dataPageType = 'product',
                            dataIsEligible = clearpayConfig.is_product_allowed ? 'true' : 'false',
                            dataCbtEnabledString = Boolean(clearpayConfig.is_cbt_enabled).toString(),
                            dataProductType = clearpayConfig.product_type,
                            squarePlacementId = 'clearpay-cta-pdp',
                            widgetContainer = clearpayConfig.placement_after_selector,
                            widgetContainerBundle = clearpayConfig.placement_after_selector_bundle,
                            priceWrapper = clearpayConfig.price_selector,
                            priceWrapperBundle = clearpayConfig.price_selector_bundle,
                            placementId = clearpayConfig.placement_id;

                        return {
                            dataShowLowerLimit: dataShowLowerLimit,
                            dataCurrency: clearpayCurrency,
                            dataIsEligible: dataIsEligible,
                            dataMPID: dataMPID,
                            dataCbtEnabledString: dataCbtEnabledString,
                            dataPlatform: dataPlatform,
                            dataPageType: dataPageType,
                            dataProductType: dataProductType,
                            widgetContainer: widgetContainer,
                            widgetContainerBundle: widgetContainerBundle,
                            squarePlacementId: squarePlacementId,
                            priceWrapper: priceWrapper,
                            priceWrapperBundle: priceWrapperBundle,
                            placementId: placementId
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

    // Process the config data
    function processClearpay() {
        fetchConfigData()
            .then(configData => {
                if (configData && !(configData.dataProductType === 'grouped')) {
                    updateClearpayAmount(configData);
                }
            })
            .catch(error => console.error("Error: ", error));
    }

    //  Add the widget
    function updateClearpayAmount(configData) {
        let wrapperHtml = document.querySelector(configData.widgetContainer),
            priceWrapper = configData.priceWrapper;
        let selector = configData.widgetContainer;

        if (configData.dataProductType === 'bundle') {
            wrapperHtml = document.querySelector(configData.widgetContainerBundle);
            priceWrapper = configData.priceWrapperBundle;
            selector = configData.widgetContainerBundle;
        }

        if (!wrapperHtml && selector) {
            if (typeof window.waitForSelector === 'function') {
                window.waitForSelector(selector)
                    .then((element) => {
                        updateHtml(element, priceWrapper, configData);
                    });
            } else {
                // Fallback to setInterval if utility not loaded
                let interval = setInterval(() => {
                    wrapperHtml = document.querySelector(selector);
                    if (wrapperHtml) {
                        clearInterval(interval);
                        updateHtml(wrapperHtml, priceWrapper, configData);
                    }
                }, 1000);
            }
        } else {
            updateHtml(wrapperHtml, priceWrapper, configData);
        }
    }

    function updateHtml(wrapperHtml, priceWrapper, configData) {
        const blockHtml = '<square-placement id="' + configData.squarePlacementId + '"' +
            'data-show-lower-limit="' + configData.dataShowLowerLimit + '"' +
            'data-currency="' + configData.dataCurrency + '"' +
            'data-is-eligible="' + configData.dataIsEligible + '"' +
            'data-amount-selector="' + priceWrapper + '"' +
            'data-mpid="'+ configData.dataMPID + '"' +
            'data-cbt-enabled="'+ configData.dataCbtEnabledString + '"' +
            'data-platform="'+ configData.dataPlatform + '"' +
            'data-page-type="' + configData.dataPageType + '"' +
            'data-placement-id="' + configData.placementId + '"></square-placement>';

        if (wrapperHtml) {
            wrapperHtml.insertAdjacentHTML('afterend', blockHtml);
        }
    }

    window.addEventListener("load", () => {
        processClearpay();
    });
})(document, window, 'script');
