(function (d, w, s) {
    let query = `mutation {
                    getClearpayConfigPdp(input: {
                        product_sku: "${clearpayProductSku}"
                        store_id: "${clearpayStoreId}"
                    }) {
                        allowed_currencies
                        is_enabled
                        mpid
                        is_enabled_ec_pdp_headless
                        product_type
                        show_lover_limit
                        is_product_allowed
                        placement_after_selector
                        placement_after_selector_bundle
                        is_cbt_enabled
                        max_amount
                        min_amount
                        price_selector
                        price_selector_bundle
                        is_in_stock
                    }
                }`;

    let graphqlEndpoint = window.location.origin + '/graphql';

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

                    if(data.errors) {
                        console.error("Error:", data.errors[0].message);
                        return null;
                    }

                    const clearpayConfig = data.data.getClearpayConfigPdp;

                    if (clearpayConfig && clearpayConfig?.is_in_stock && clearpayConfig?.is_product_allowed && clearpayConfig?.product_type != "grouped") {
                        const event = new CustomEvent('showHeadlessEC', { detail: { clearpayConfig} });
                        document.dispatchEvent(event);
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
    window.addEventListener("load", (event) => {
        if (clearpayProductSku !== "") {
            fetchConfigData();
        }
    });

})(document, window, 'script');
