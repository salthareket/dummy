jQuery(document).ready(function ($) {

    function composer_message($message = "", $action = "", $type = ""){
        if($message == ""){
            $(".alert").removeClass("show").addClass("d-none").empty();
            if($action != "loading"){
                return;
            }else{
                $message = "Please wait...";
            }
        }
        let $class = "";
        switch($action){
            case "install" :
                $class = "alert-success";
                break;
            case "update" :
                $class = "alert-success";
                break;
            case "remove" :
            case "error" :
                $class = "alert-danger";
                break;
            case "nothing" :
                $class = "alert-secondary";
                break;
            case "loading" :
                $class = "alert-secondary loading loading-xs";
                break;
        } 
        let alert = $(".alert[data-action='" + $type +"']");
        alert
        .removeClass("loading alert-success alert-danger alert-secondary alert-info show d-none")
        .addClass($class)
        .html($message)
        .addClass("show");
    }
    $('#install-theme-button').on('click', function () {
        var $button = $(this);
        $button.prop('disabled', true).text('Installing...');

        composer_message("", "loading", "update");

        $.ajax({
            url: installAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'install_theme_package',
                _ajax_nonce: installAjax.nonce
            },
            success: function (response) {
                if (response.success) {
                    composer_message(response.data.message, response.data.action, "update");
                    location.reload();
                } else {
                    composer_message(response.data.message, "error", "update");
                }
                $button.text('Please wait...');
            },
            error: function () {
                composer_message('AJAX request failed.', "error", "update");
                $button.prop('disabled', false).text('Start Installation Again');
            }
        });
    });

});
