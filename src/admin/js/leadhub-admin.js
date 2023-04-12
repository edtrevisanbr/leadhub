function logDebug(message) {
    if (typeof console !== "undefined" && console.debug) {
        console.debug("Leadhub Debug:", message);
    }
}

jQuery(document).ready(function ($) {
    logDebug("Document ready");
    $("#test_api_connection").click(function () {
        logDebug("Button clicked");

        var baseUrl = $("input[name='leadhub_mautic_base_url']").val();
        var publicKey = $("input[name='leadhub_mautic_public_key']").val();
        var secretKey = $("input[name='leadhub_mautic_secret_key']").val();

        logDebug("Values: " + baseUrl + ", " + publicKey + ", " + secretKey);

        if (baseUrl && publicKey && secretKey) {
            logDebug("Sending AJAX request");
            $.post(
                ajaxurl,
                {
                    action: "test_api_connection",
                    baseUrl: baseUrl,
                    publicKey: publicKey,
                    secretKey: secretKey,
                },
                function (response) {
                    logDebug("AJAX response received");
                    logDebug(response);
                    if (response.success) {
                        alert(response.data.message);
                    } else {
                        alert(response.data.message);
                    }
                }
            );
        } else {
            alert("Por favor, preencha todos os campos necessários antes de testar a conexão com a API.");
        }
    });
});
