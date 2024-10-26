(function ($) {

  // Check if parameters from php were passed
  if (typeof one_acc_woo_params === 'undefined') {
    return false
  }

  var checkout_status = localStorage.getItem('checkout_status');
  if (!checkout_status) {
    localStorage.setItem('checkout_status', 'av_failed');
  }


   // Set cookie function
  function setCookie(cname, cvalue, exdays) {
    var d = new Date();
    d.setTime(d.getTime() + (exdays * 24 * 60 * 60 * 1000));
    var expires = "expires="+ d.toUTCString();
    document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
  }


  function uuid() {
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
      var r = Math.random() * 16 | 0, v = c == 'x' ? r : (r & 0x3 | 0x8);
      return v.toString(16);
    });
  }



  var checkout_after_statusapi = localStorage.getItem("checkout_status_on_place_order");
  var checkout_after_statusdata = localStorage.getItem("checkout_status_on_place_order2")

  if( checkout_after_statusapi != ''  && one_acc_woo_params.popup_placement === 'on_place_order'  && typeof order !== 'undefined')
  {   

      
      if(checkout_after_statusapi == 'AV_FAILED'){
        
        $.ajax({
          type: "post",
          dataType: "json",
          url: ajax_object.ajax_url,
          data: { action: 'check_pending_status', status:checkout_after_statusapi,orderId: order.id,platformId:'WOO' }
        });

        localStorage.setItem('sessionID', order.id);
      }

      $.ajax({
        type: "post",
        dataType: "json",
        url: one_acc_woo_params.ajax_url,
        data: { action: 'thankyou_status_update', user_status:checkout_after_statusdata, order_id: order.id }
      });


      var currentURL = window.location.href;
      var urlParams = new URLSearchParams(currentURL);
      var ageverifyValue = urlParams.get("ageverify"); 
      if (ageverifyValue == 'unverify' ) {

        $.ajax({
          type: "post",
          dataType: "json",
          url: ajax_object.ajax_url,
          data: { action: 'check_update_status', status:checkout_after_statusapi,orderId: order.id, platformId:'WOO' }
        });

        localStorage.setItem('sessionID', order.id);
        //console.log(checkout_after_statusapi);
        setTimeout(() => { location.reload();  }, 150);
      }

      localStorage.setItem('checkout_status_on_place_order', '');

  }


  function order_session() {

    var order_session = '';
    if (one_acc_woo_params.popup_placement === 'after_place_order' && typeof order !== 'undefined') {
        var order_session = order.id;
    }

    if (one_acc_woo_params.popup_placement === 'on_place_order' ) {
        var order_session = jQuery('#last_order_id').val();

        var currentURL = window.location.href;
        var urlParams = new URLSearchParams(currentURL);
        var ageverifyValue = urlParams.get("ageverify");

        if(ageverifyValue == 'unverify'){
          var order_session = order.id;
        }

    }
    return order_session;

  }


  // Main Code

  let current_status = jQuery('#current_status').val();

      PUSH_API.init({
        authCode: uuid(),
        avLevel: one_acc_woo_params.scope,
        clientId: one_acc_woo_params.client_id,
        sessionId: order_session(),
        onComplete: function (response) {
          let respStatus = response.status.toLowerCase()
          let caprespStatus = response.status;


          if (one_acc_woo_params.popup_placement === 'after_place_order'  ) { // Check validation popup placement
            $.ajax({
              type: "post",
              dataType: "json",
              url: one_acc_woo_params.ajax_url,
              data: { action: 'thankyou_status_update', user_status:respStatus, order_id: order.id }
            });

            if(respStatus == 'av_failed'){

              $.ajax({
                type: "post",
                dataType: "json",
                url: ajax_object.ajax_url,
                data: { action: 'check_pending_status', status:caprespStatus,orderId: order.id,platformId:'WOO' }
                });

            }

            // Show alert with verification status
            if (respStatus == 'av_failed') {

              localStorage.setItem('checkout_status', 'av_failed');
              setTimeout(() => {
                alert(`You have failed to verify your age at this times. Whilst your order has been placed, it will be held by ${ window.location.hostname } pending confirmation of your 18+ status.`);
              }, 1500);
            }else if (respStatus == 'av_success') {

              var currentURL = window.location.href;
              var urlParams = new URLSearchParams(currentURL);
              var ageverifyValue = urlParams.get("ageverify"); 
              if (ageverifyValue == 'unverify') {

                  $.ajax({
                    type: "post",
                    dataType: "json",
                    url: ajax_object.ajax_url,
                    data: { action: 'check_update_status', status:caprespStatus, orderId: order.id,platformId:'WOO' }
                  });
                  
              }

              localStorage.setItem('checkout_status', 'av_success');
              setTimeout(() => {
                alert('Age verification succeed');
              }, 1500);
              
            }

            var currentURL = window.location.href;
            var urlParams = new URLSearchParams(currentURL);
            var ageverifyValue = urlParams.get("ageverify"); 
            if (ageverifyValue === 'unverify') {
              setTimeout(() => { location.reload();  }, 2000);
            }

          }else{

            $('#user-status').val(respStatus)
            localStorage.setItem('checkout_status_on_place_order', caprespStatus);
            localStorage.setItem('checkout_status_on_place_order2', respStatus);

            if (respStatus == 'av_failed' && one_acc_woo_params.popup_placement === 'on_place_order') {

                setTimeout(() => {
                  alert(`You have failed to verify your age at this time. Whilst your order has been placed, it will be held by ${ window.location.hostname } pending confirmation of your 18+ status.`);
                  $('#place_order').trigger('click');
                }, 1500)

                localStorage.setItem('checkout_status', 'av_failed');
                 $('#confirm-order-flag').val('') 
                $('#user-status').val(respStatus)
                //console.log('av_failed');

            }else if (respStatus == 'av_success' && one_acc_woo_params.popup_placement === 'on_place_order' ) {

                setTimeout(() => {
                  alert('Age verification succeed');
                }, 1500)

                 $('#confirm-order-flag').val('') 
                $('#user-status').val(respStatus)

                localStorage.setItem('checkout_status', 'av_success');
                var currentURL = window.location.href;
                var urlParams = new URLSearchParams(currentURL);
                var ageverifyValue = urlParams.get("ageverify"); 
                if (ageverifyValue === 'unverify') {

                  setTimeout(() => { location.reload();  }, 2000);
                }

                $('#place_order').trigger('click');
                //console.log('av_success');

            }else{

              //console.log('click');
              $('#place_order').trigger('click');
            }


          }

       
      }
        
      })

  


  // Add hidden fields to transfer order confirmation flag and user-status
  let checkout_form = $('form.checkout')
  checkout_form.on('checkout_place_order', function () {

    let selected_billing_country = jQuery('#billing_country').val();

    if ($('#confirm-order-flag').length == 0 && one_acc_woo_params.popup_placement === 'on_place_order' && selected_billing_country == 'GB')  {
      checkout_form.append('<input type="hidden" id="confirm-order-flag" name="confirm-order-flag" value="1">')
      checkout_form.append('<input type="hidden" id="user-status" name="user-status" value="">')
    }

    return true
  })

   // Validate checkout attempt
  $(document.body).on('checkout_error', function () {
    let error_count = $('.woocommerce-error li').length
    if (error_count == 1) { // Validation Passed (Just the Fake Error I Created Exists)
      //$('.woocommerce-error').css('display', 'none') // Hide fake error

      const userInfo = {
        forename: $('#billing_first_name').val(),
        surname: $('#billing_last_name').val(),
        email: $('#billing_email').val(),
        msisdn: $('#billing_phone').val(),
        building: $('#billing_address_2').val(),
        dob: null,
        street: $('#billing_address_1').val(),
        city: $('#billing_city').val(),
        postCode: $('#billing_postcode').val(),
        country: $('#billing_country').val()
      }
      const dataArr = filterData(one_acc_woo_params.scope, userInfo);
      if(one_acc_woo_params.popup_placement === 'on_place_order'){
        $('.woocommerce-error').css('display', 'none')
        PUSH_API.validate(dataArr)
      }
      
    } else {

      // Validation Failed (Real Errors Exists, Remove the Fake One)
      //$('.woocommerce-error').css('display', 'block')
      $('.woocommerce-error li:contains("Validation Error Status")').css('display', 'none')
    }
  })


  // Filter data that depends on validation level
  function filterData(level, userData) {
    let filteredData;
    switch (level) {
      case '1':
        filteredData = {
          forename: userData.forename,
          surname: userData.surname,
          email: userData.email,
          msisdn: userData.msisdn,
          building: userData.building,
          street: userData.street,
          city: userData.city,
          postCode: userData.postCode,
          country: userData.country
        }
        break
      case '2':
        filteredData = {
          forename: userData.forename,
          surname: userData.surname,
          email: userData.email,
          msisdn: userData.msisdn,
          building: userData.building,
          street: userData.street,
          city: userData.city,
          postCode: userData.postCode,
          country: userData.country
        }
        break
      default:
        filteredData = {
          forename: userData.forename,
          surname: userData.surname,
          email: userData.email,
          msisdn: userData.msisdn,
          dob: null,
          building: userData.building,
          street: userData.street,
          city: userData.city,
          postCode: userData.postCode,
          country: userData.country
        }
    }
    return filteredData
  }

  // Check order existence on thank you page (depends on popup_placement field)
  if (typeof order !== 'undefined' && one_acc_woo_params.current_page === 'order_received' && one_acc_woo_params.popup_placement === 'after_place_order' && current_status != 'av_success') {

    const dataArr = filterData(one_acc_woo_params.scope, order);
    let thank_popup = jQuery('#thank_popup').val();
    var currentURL = window.location.href;
    var urlParams = new URLSearchParams(currentURL);
    var ageverifyValue = urlParams.get("ageverify");
    if(thank_popup != 'open' || ageverifyValue == 'unverify'){
          setTimeout(() => PUSH_API.validate(dataArr), 1000);
    }    

  }


  if(typeof order !== 'undefined' && one_acc_woo_params.current_page === 'order_received' && one_acc_woo_params.popup_placement === 'on_place_order' && current_status != 'av_success') {

    const dataArr = filterData(one_acc_woo_params.scope, order);
    var currentURL = window.location.href;
    var urlParams = new URLSearchParams(currentURL);
    var ageverifyValue = urlParams.get("ageverify");

    if(ageverifyValue == 'unverify'){
          setTimeout(() => PUSH_API.validate(dataArr), 1000);
    }    
  }




}(jQuery))



