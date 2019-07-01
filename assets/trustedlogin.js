(function( $ ) {
 
    "use strict";
     
    $(document).ready( function(){

        jconfirm.pluginDefaults.useBootstrap=false;

        function offerRedirectToSupport(response,tl_obj){

            if (typeof response.data == 'object'){
                var autoLoginURI = response.data.siteurl + '/' + response.data.endpoint + '/' + response.data.identifier;
                var contentHTML = '<p>Please <a href="'+tl_obj.plugin.support_uri+'" target="_blank">click here</a> to go to the '+tl_obj.plugin.title+' Support Forum. </p><p><em>Pro-tip:</em>  By sharing the following URL it will give them Automatic Support Access:</p> <pre>' + autoLoginURI +' </pre>';
                var titleText = 'Support Access Created';
            } else {
                var titleText = 'Error syncing Support User to '+tl_obj.plugin.title ;
                var contentHTML = '<p>Unfortunately the support user could not be created or synced to '+tl_obj.plugin.title+' automatically.</p><p>Please <a href="'+tl_obj.plugin.support_uri+'" target="_blank">click here</a> to go to the '+tl_obj.plugin.title+' Support site instead. </p>';
            }
            

            $.alert({
                icon: 'fa fa-check',
                theme: 'material',
                title: titleText,
                type: 'orange',
                content: contentHTML,
                buttons: {
                    goToSupport: {
                        text: 'Go To '+tl_obj.plugin.title+' Support Site',
                        action: function(goToSupportButton){
                            window.open(tl_obj.plugin.support_uri,'_blank');
                            return false; // you shall not pass
                        },
                    },
                    close: {
                        text: 'Close'
                    },
                }
            });
        }

        $('body').on('click','#trustedlogin-grant',function(e){
            $.confirm({
                title: tl_obj.intro,
                content: tl_obj.description + tl_obj.details,
                theme: 'material',
                type: 'blue',
                buttons: {
                    confirm: function () {
                        
                        var data = {
                            'action': 'tl_gen_support',
                            '_nonce': tl_obj._n,
                        };

                        console.log(data);

                        $.post(tl_obj.ajaxurl, data, function(response) {
                            console.log(response);
                            if (response.success && typeof response.data == 'object'){
                                var autoLoginURI = response.data.siteurl + '/' + response.data.endpoint + '/' + response.data.identifier;
                                
                                $.alert({
                                    icon: 'fa fa-check',
                                    theme: 'material',
                                    title: 'Support Access Granted',
                                    type: 'green',
                                    content: 'DevNote: The following URL will be used to autologin support <a href="'+autoLoginURI+'">Support URL</a> '
                                });
                            } else if (!response.success && response.data.message =='Sync Issue') {
                                offerRedirectToSupport(response,tl_obj);
                            } else {
                                $.alert({
                                    icon: 'fa fa-times-circle',
                                    theme: 'material',
                                    title: 'Support Access NOT Granted',
                                    type: 'red',
                                    content: 'Got this from the server: ' + JSON.stringify(response)
                                });
                            }
                            
                        }).fail(function(response) {
                            offerRedirectToSupport(response.responseJSON,tl_obj);
                        });
                    },
                    cancel: function () {
                        $.alert({
                            icon: 'fa fa-warning',
                            theme: 'material',
                            title: 'Action Cancelled',
                            type: 'orange',
                            content: 'A support account for '+tl_obj.plugin.title+' has <em><strong>NOT</strong></em> been created.'
                        });
                    }
                }
            });
        });

    } ); 
 
})(jQuery);

