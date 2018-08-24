(function( $ ) {
	var check_count = 0;
	var allow_button_click = false;
	var countdown = 0;

    var local = {
    	reset: function () {

    	},
        init: function () {

        	$.initialize("#local-form", function() {
	    		$("#local-qr-code").qrcode( {
					    width: 200,
					    height: 200,
					    text: $("#local-qr-code").data('contents')
					}
	    		);

	    		clipboard = new Clipboard('.copy');
			    clipboard.on('success', function(e) {
			    	var el = $(e.trigger);

			        el.text( el.data('success-label') );
			        setTimeout(function(){
			        	el.text( el.data('clipboard-text') );
			        },300);
			        return false;
			    });

				countdown = $('.local-countdown').data('minutes') * 60 * 1000;

                // ignore button presses while waiting
                $('#place_order').on( 'click',function () {
                    if($( '#local-form' ).is(':visible') && allow_button_click == false){
	                    return false;
	                }
                });

	    	});

        },
        checkForPayment: function(){
        	check_count++;
            $.ajax({
                url: local_vars.wc_ajax_url,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'check_local_payment',
                    nonce: local_vars.nonce
                }
            }).done(function (res) {
                console.log("Match: " + res);
                if(res.result == true && res.match == true){
                	$("#tx_hash").val( res.tx_hash );
                    allow_button_click = true;
                    $( '#place_order' ).trigger( 'click');
                    return;
                }
                setTimeout(function() {
                    local.checkForPayment();
                }, 3000);
            });
        },
    }

    local.init();

    setTimeout(function() {
        local.checkForPayment();
    }, 3000);

    setInterval(function(){
		countdown -= 1000;

		var minutes = Math.floor(countdown / (60 * 1000));
		var seconds = Math.floor((countdown - (minutes * 60 * 1000)) / 1000);

		if (countdown <= 0) {
			if($( '#local-form' ).is(':visible')){
	            $( 'body' ).trigger( 'update_checkout' );
	        }
		} else {
			$('.local-countdown').html(minutes + ":" + (seconds < 10 ? 0 : '') + seconds);
		}

	}, 1000);


})( jQuery );
