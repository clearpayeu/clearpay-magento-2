(function (d, w, s) {
    let query = `mutation {
                    getClearpayConfigCart(input: {
                        cart_id: "${clearpayCartId}"
                        store_id: "${clearpayStoreId}"
                    }) {
                        allowed_currencies
                        is_enabled
                        mpid
                        is_enabled_ec_cart_page_headless
                        show_lover_limit
                        is_product_allowed
                        is_cbt_enabled
                        placement_after_selector
                        price_selector
                        max_amount
                        min_amount
                        is_virtual
                    }
                }`;

    let graphqlEndpoint = window.location.origin + '/graphql';
    let configData;

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

                    if (clearpayConfig) {
                        const event = new CustomEvent('showHeadlessCart', {detail: {clearpayConfig}});
                        document.dispatchEvent(event);
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

    window.addEventListener("load", (event) => {
        if (clearpayCartId !== "") {
            fetchConfigData();
        }
    });

    // Create the custom event
    window.reloadCartPage = new CustomEvent('reloadCartPage');

    // Attach an event listener
    document.addEventListener('reloadCartPage', function() {
        if (clearpayCartId !== "") {
            fetchConfigData();
        }
    });

})(document, window, 'script');
