$(document).ready(function() {
    if ($('.active-form-edit-mode').is(':visible')) {
        $(".user-create .actions button#submit").prop("disabled", false);
        $("#nextBtn").prop("disabled", false);
    }else{
        $(".user-create .actions button#submit").prop("disabled", true);
        $("#nextBtn").prop("disabled", true);
    }
});
$(document).ready(function(){
    var checkedValue = $('input[name="logo_type"]:checked').val();
    if (checkedValue) {
        if (checkedValue === 'default') {
           $('.site-qrcode-logo-section-wrap-option-1').show();
           $('.site-qrcode-logo-section-wrap-option-2').hide();
       }else if (checkedValue === 'uploaded'){
           $('.site-qrcode-logo-section-wrap-option-1').hide();
           $('.site-qrcode-logo-section-wrap-option-2').show();
           if ($('#image_preview .img-div').length === 0) {
               $('#image_preview').removeClass('site-wrap-list').empty();
           }
       }
   }else{
    $('#default').prop("checked", true);
    $('.site-qrcode-logo-section-wrap-option-1').show();
    $('.site-qrcode-logo-section-wrap-option-2').hide();
}

$('#default').change(function(){
    if(this.checked) {
      $('.site-qrcode-logo-section-wrap-option-1').show();
      $('.site-qrcode-logo-section-wrap-option-2').hide();
  }
});

$('#uploaded').change(function(){
    if(this.checked) {
        $('.site-qrcode-logo-section-wrap-option-2').show();
        $('.site-qrcode-logo-section-wrap-option-1').hide();
        if ($('#image_preview .img-div').length === 0) {
            $('#image_preview').removeClass('site-wrap-list').empty();
        }
    }
});
});

function checkImageProperties(input) {
    var submitButton = document.getElementById("submit");

    if (input.files && input.files[0]) {
        var file = input.files[0];
        var fileSize = file.size;
        var fileType = file.type;
        var maxSizeInBytes = 500000;
        var img = new Image();

        img.src = window.URL.createObjectURL(file);
        img.onload = function() {
            var width = img.naturalWidth;
            var height = img.naturalHeight;

            if (fileSize > maxSizeInBytes) {
                document.getElementById("imageError").textContent = "Maximum file size allowed is 500kb.";
                input.value = "";
                submitButton.disabled = true;
            } else if (width < 250 || height < 250) {
                document.getElementById("imageError").textContent = "Minimum resolution required is 250x250.";
                input.value = "";
                submitButton.disabled = true;
            } else if (width > 1280 || height > 1280) {
                document.getElementById("imageError").textContent = "Maximum resolution allowed is 1080x1080.";
                input.value = "";
                submitButton.disabled = true;
            } else if(fileType !== "image/jpeg" && fileType !== "image/png" && fileType !== "image/jpg"){
                document.getElementById("imageError").textContent = "Only JPG and PNG images are allowed.";
                input.value = "";
                submitButton.disabled = true;
            }else {
                document.getElementById("imageError").textContent = "";
                submitButton.disabled = false;
            }
        };
    }
}
$('.image-select').select2({
    templateResult: formatOption
});

function formatOption(option) {
    if (!option.id) {
        return option.text;
    }

    var imageSrc = $(option.element).data('image');
    if (!imageSrc) {
        return option.text;
    }

    var $option = $(
        '<span><img src="' + imageSrc + '" class="select-image" width="50" height="50"/> ' + option.text + '</span>'
        );

    return $option;
}
function downloadQRCode(filename, format, qrcode_name) {
    var link = document.createElement("a");
    link.href = filename;
    if (format === "pdf") {
        link.download = "qrcode.pdf";
        link.target = "_blank";
    } else {
        if (qrcode_name == '') {
            link.download = "qrcode." + format;
        }else{
            link.download = qrcode_name+"."+ format;
        }
    }

    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

$(document).ready(function() {
    var currentStep = 0;
    var steps = $(".step");
    var nextBtn = $("#nextBtn");
    var prevBtn = $("#prevBtn");
    var actions = $(".actions");
    var get_qrcode_id = $("#get_qrcode_id").val();

    function goToStep(stepIndex) {
        if (stepIndex < 0 || stepIndex >= steps.length) {
            return;
        }
        currentStep = stepIndex;

        // 1. Synchronize Stepper Indicators
        $(".step-menu-wrap .nav-link").removeClass("active");
        $(".step-menu-wrap .nav-link").eq(stepIndex).addClass("active");
        $(".step-menu-wrap .nav-link").each(function(idx) {
            if (idx < stepIndex) {
                $(this).addClass("completed");
            } else {
                $(this).removeClass("completed");
            }
        });

        // 2. Switch Visible Step Content
        steps.hide().eq(stepIndex).show();

        // 3. Update Navigation Action Buttons
        if (stepIndex === 0) {
            // Step 1 (Setup): Show ONLY Next. Previous MUST NOT appear.
            prevBtn.hide();
            nextBtn.show();
            nextBtn.text("Next");
            $('.button-custom-genrate').remove();
            checkUrlValidity();
        } else if (stepIndex === steps.length - 1) {
            // Final Step (Done): Show Previous and Finish ONLY. Next MUST NOT appear.
            prevBtn.show();
            prevBtn.prop("disabled", false);
            nextBtn.hide();

            // Ensure single Finish button is present and configured
            if ($('.button-custom-genrate').length === 0) {
                var generatebtn = '<button type="button" class="btn btn-primary button-custom-genrate rs-btn rs-btn--primary" id="submit" name="submit">Finish</button>';
                actions.append(generatebtn);
            }
            var nameVal = $.trim($('#qr-code-name').val() || '');
            if (nameVal !== '' && !/[^a-zA-Z0-9, -]/.test(nameVal)) {
                $('.button-custom-genrate').prop("disabled", false);
            } else {
                $('.button-custom-genrate').prop("disabled", true);
            }
        } else {
            // Step 2 (Customize): Show Previous and Next.
            prevBtn.show();
            prevBtn.prop("disabled", false);
            nextBtn.show();
            nextBtn.text("Next");
            nextBtn.prop("disabled", false);
            $('.button-custom-genrate').remove();
        }
    }

    function checkUrlValidity() {
        var urlVal = $.trim($('#qr_gen_form_website_url').val() || '');
        if (urlVal === '') {
            nextBtn.prop("disabled", true);
            return false;
        }
        var urlRegex = new RegExp('^(https?:\\/\\/)?' +
            '((([a-z\\d]([a-z\\d-]*[a-z\\d])*)\\.)+[a-z]{2,}|' +
            '((\\d{1,3}\\.){3}\\d{1,3}))' +
            '(\\:\\d+)?(\\/[-a-z\\d%_.~+]*)*' +
            '(\\?[;&a-z\\d%_.~+=-]*)?' +
            '(\\#[-a-z\\d_]*)?$', 'i');
        if (!urlRegex.test(urlVal)) {
            nextBtn.prop("disabled", true);
            return false;
        }
        nextBtn.prop("disabled", false);
        return true;
    }

    // Initialize Step 1
    goToStep(0);
    if (get_qrcode_id || $('.active-form-edit-mode').is(':visible')) {
        nextBtn.prop("disabled", false);
    }

    nextBtn.on('click', function(e) {
        if (currentStep === 0) {
            if (!checkUrlValidity()) {
                $('#qr_gen_form_website_url_error').text('Please enter a valid URL');
                return false;
            }
            $('#qr_gen_form_website_url_error').text('');
        }
        if (currentStep < steps.length - 1) {
            goToStep(currentStep + 1);
            if (typeof updateGenereate === 'function') {
                updateGenereate(e);
            }
        }
    });

    prevBtn.on('click', function(e) {
        e.preventDefault();
        if (currentStep > 0) {
            goToStep(currentStep - 1);
        }
    });

    $('#qr_gen_form_website_url').on('input keyup focusout', function() {
        var urlVal = $.trim($(this).val() || '');
        if (urlVal === '') {
            $('#qr_gen_form_website_url_error').text('Required field! Please enter the destination URL.');
            nextBtn.prop("disabled", true);
            return;
        }
        var urlRegex = new RegExp('^(https?:\\/\\/)?' +
            '((([a-z\\d]([a-z\\d-]*[a-z\\d])*)\\.)+[a-z]{2,}|' +
            '((\\d{1,3}\\.){3}\\d{1,3}))' +
            '(\\:\\d+)?(\\/[-a-z\\d%_.~+]*)*' +
            '(\\?[;&a-z\\d%_.~+=-]*)?' +
            '(\\#[-a-z\\d_]*)?$', 'i');
        if (!urlRegex.test(urlVal)) {
            $('#qr_gen_form_website_url_error').text('Please enter a valid URL');
            nextBtn.prop("disabled", true);
        } else {
            $('#qr_gen_form_website_url_error').text('');
            nextBtn.prop("disabled", false);
        }
    });

    $('#qr-code-name').on('input keyup focusout', function() {
        var text = $.trim($(this).val() || '');
        var finishBtn = $('.button-custom-genrate');
        if (text === '') {
            $('#qr_gen_form_name_error').html('<i class="fa fa-info-circle" aria-hidden="true"></i> Required field! Please enter the Name.');
            finishBtn.prop("disabled", true);
        } else if (/[^a-zA-Z0-9, -]/.test(text)) {
            $('#qr_gen_form_name_error').html('<i class="fa fa-info-circle" aria-hidden="true"></i> Only letters, numbers, and spaces are allowed! Special characters are not allowed!');
            finishBtn.prop("disabled", true);
        } else {
            $('#qr_gen_form_name_error').text('');
            finishBtn.prop("disabled", false);
        }
    });
});

/** QR Code Form submit Code start */

$(document).ready(function() {

    var oldForm  = $('#stepForm').serialize();
    var isFinishSubmitting = false;

    $(document).on('click', '.button-custom-genrate', function(e) {
        e.preventDefault();

        if (isFinishSubmitting) {
            return false;
        }

        // Validate QR Name input before locking
        var nameInput = $('#qr-code-name');
        if (nameInput.length) {
            var nameVal = $.trim(nameInput.val());
            if (nameVal === '') {
                $('#qr_gen_form_name_error').html('<i class="fa fa-info-circle" aria-hidden="true"></i> Required field! Please enter the Name.');
                $(this).prop("disabled", true);
                return false;
            }
            if (/[^a-zA-Z0-9, -]/.test(nameVal)) {
                $('#qr_gen_form_name_error').html('<i class="fa fa-info-circle" aria-hidden="true"></i> Only letters, numbers, and spaces are allowed! Special characters are not allowed!');
                $(this).prop("disabled", true);
                return false;
            }
        }

        isFinishSubmitting = true;
        $('.button-custom-genrate').prop('disabled', true);
        $('#prevBtn').prop('disabled', true);

        updateGenereate(e, false, true);
    });

    $('#stepForm').on('submit', function(e) {
        e.preventDefault();
        if ($('.button-custom-genrate').length && !isFinishSubmitting) {
            $('.button-custom-genrate').trigger('click');
        }
    });

    $(document).on('click', '#rs-qr-go-back-btn', function(e) {
        var href = $(this).attr('href');
        if (href && href !== '#') {
            window.location.href = href;
        }
    });

    $('#templates_scroll').on('click', function(e){
       updateGenereate(e);
   });

    $('.logo-gallery').on('click', function(e){

        updateGenereate(e);
    });

    $('.qr-frame-container').on('click', function(e){

        updateGenereate(e);
    });

    function handleUploadValidationError(errorMsg) {
        $('.text-error-msg').text(errorMsg);
        $('#qr_gen_form_name_error').text(errorMsg);
        $('#qr_gen_form_logo').val('');
        $('#image_preview').empty().removeClass('site-wrap-list');
        $('.drag-file-area').removeClass('drag-over');
    }

    function renderUploadPreview(file) {
        var previewUrl = window.URL.createObjectURL(file);
        var safeName = $('<div>').text(file.name).html();
        var previewHtml = '<div class="img-div" id="img-div-uploaded">' +
            '<img src="' + previewUrl + '" class="img-responsive image img-thumbnail" title="' + safeName + '" alt="' + safeName + '">' +
            '<div class="middle">' +
                '<button type="button" id="action-icon" value="img-div-uploaded" class="btn btn-danger" role="' + safeName + '" title="Remove Logo">' +
                    '<i class="fa fa-trash"></i>' +
                '</button>' +
            '</div>' +
        '</div>';
        $('#image_preview').html(previewHtml).addClass('site-wrap-list');
    }

    function validateAndProcessLogoFile(file, e) {
        if (!file) {
            $('#image_preview').empty().removeClass('site-wrap-list');
            $('.text-error-msg').text('');
            return;
        }

        // 1. File Size Validation (Max 500 KB = 512,000 bytes)
        var maxBytes = 500 * 1024;
        if (file.size > maxBytes) {
            handleUploadValidationError('Maximum file size allowed is 500 KB.');
            return;
        }

        // 2. MIME Type Validation
        var allowedMimes = ['image/jpeg', 'image/png', 'image/jpg'];
        var mimeLower = (file.type || '').toLowerCase();
        if (allowedMimes.indexOf(mimeLower) === -1) {
            handleUploadValidationError('Only JPG and PNG images are allowed.');
            return;
        }

        // 3. Image Dimensions Validation (Min 250x250, Max 1080x1080)
        var img = new Image();
        var blobUrl = window.URL.createObjectURL(file);
        img.src = blobUrl;
        img.onload = function() {
            var width = img.naturalWidth || img.width;
            var height = img.naturalHeight || img.height;
            window.URL.revokeObjectURL(blobUrl);

            if (width < 250 || height < 250) {
                handleUploadValidationError('Minimum resolution required is 250x250 pixels.');
                return;
            }
            if (width > 1080 || height > 1080) {
                handleUploadValidationError('Maximum resolution allowed is 1080x1080 pixels.');
                return;
            }

            // Valid image: Clear error messages
            $('.text-error-msg').text('');
            $('#qr_gen_form_name_error').text('');

            // Render thumbnail preview with remove button
            renderUploadPreview(file);

            // Trigger QR generation with the valid file
            updateGenereate(e, true, false);
        };
        img.onerror = function() {
            window.URL.revokeObjectURL(blobUrl);
            handleUploadValidationError('Uploaded file is not a valid image format.');
        };
    }

    $('#qr_gen_form_logo').on('change', function(e){
        var file = (this.files && this.files.length > 0) ? this.files[0] : null;
        validateAndProcessLogoFile(file, e);
    });

    // Drag-and-drop support on .drag-file-area
    var $dragFileArea = $('.drag-file-area');
    $dragFileArea.on('dragover dragenter', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).addClass('drag-over');
    });
    $dragFileArea.on('dragleave dragend', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).removeClass('drag-over');
    });
    $dragFileArea.on('drop', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).removeClass('drag-over');
        var dt = e.originalEvent.dataTransfer;
        if (dt && dt.files && dt.files.length > 0) {
            var input = document.getElementById('qr_gen_form_logo');
            if (input) {
                input.files = dt.files;
                validateAndProcessLogoFile(dt.files[0], e);
            }
        }
    });

    $('body').on('click', '#action-icon', function(evt){
        evt.preventDefault();
        var divName = this.value;
        if (divName) {
            $(`#${divName}`).remove();
        }
        $('#image_preview').empty().removeClass('site-wrap-list');
        $('#qr_gen_form_logo').val('');
        $('.text-error-msg').text('');
        $('#qr_gen_form_name_error').text('');
        
        updateGenereate(evt, true, false);
    });


    function updateGenereate(e, is_upload_img = false, is_finish = false){
        if (e && typeof e.preventDefault === 'function') {
            e.preventDefault();
        }

        var form = $('#stepForm')[0];
        var formData = new FormData(form);

        if(is_finish == true){
            formData.append("finish", "finish");
        }

        newFormData = $('#stepForm').serialize();
        if(oldForm !== newFormData){
            qrCodeGenerate(formData);
        } else if(is_finish == true){
            qrCodeGenerate(formData);
        } else if(is_upload_img == true) {
            qrCodeGenerate(formData);
        } else {
            console.log("Else");
        }
    }


    function qrCodeGenerate(formData) {
        var csrfToken = $('meta[name="csrf-token"]').attr('content') || $('input[name="_csrf"]').val();
        if (csrfToken && typeof formData.has === 'function' && !formData.has('_csrf')) {
            formData.append('_csrf', csrfToken);
        }

        var isFinish = (typeof formData.has === 'function' && formData.has('finish'))
            || (typeof formData.get === 'function' && formData.get('finish') === 'finish');

        $.ajax({
            url: $("#stepForm").attr("action"),
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType : 'json',
            beforeSend: function(xhr) {
                $("#loader").show();
                if (csrfToken) {
                    xhr.setRequestHeader('X-CSRF-Token', csrfToken);
                }
            },
            success: function(response) {
                console.log(response);
                if (response && response.status === 1) {
                    if (isFinish) {
                        var targetUrl = response.viewUrl;
                        if (!targetUrl && response.id) {
                            var appBase = $('meta[name="app-base-url"]').attr('content') || '';
                            targetUrl = appBase + '/qrcode/view?id=' + response.id;
                        }
                        if (targetUrl) {
                            window.location.href = targetUrl;
                            return;
                        }
                    }
                    if (response.html) {
                        $("#qrCodeImage").html(response.html);
                    }
                } else if (response && response.status === 0) {
                    var errorMsg = response.message || 'Failed to generate QR Code.';
                    $('#qr_gen_form_name_error').text(errorMsg);
                    var msgLower = errorMsg.toLowerCase();
                    if (msgLower.indexOf('logo') !== -1 ||
                        msgLower.indexOf('resolution') !== -1 ||
                        msgLower.indexOf('file size') !== -1 ||
                        msgLower.indexOf('image') !== -1) {
                        handleUploadValidationError(errorMsg);
                    }
                    if (response.html) {
                        $("#qrCodeImage").html(response.html);
                    }
                    isFinishSubmitting = false;
                    $('.button-custom-genrate').prop('disabled', false);
                    $('#prevBtn').prop('disabled', false);
                } else if (response && response.html) {
                    $("#qrCodeImage").html(response.html);
                }
            },
            error: function(xhr, status, error) {
                console.log(error);
                $("#loader").hide();
                isFinishSubmitting = false;
                $('.button-custom-genrate').prop('disabled', false);
                $('#prevBtn').prop('disabled', false);
            },
            complete : function(event, xhr, settings) {
                /* executes whenever an AJAX request completes */
                oldForm  = $('#stepForm').serialize();
                $("#loader").hide();
            }
        });
    }



    
});

/** QR Code Form submit Code end */

  // tab js 
jQuery('.tab-link').click( function() {
    var tabID = jQuery(this).attr('data-tab');
    jQuery(this).addClass('active').siblings().removeClass('active');
    jQuery('#tab-'+tabID).addClass('active').siblings().removeClass('active');
});

var templateDefaultLogos = {
    'bitcoin': 'bitcoin',
    'facebook': 'facebook',
    'youtube': 'youtube',
    'instagram-frame': 'instagram',
    'classic-frame': 'youtube'
};

var templateDefaultFrames = {
    'classic-frame': 'banner-bottom'
};

var userHasManualLogo = false;
var userHasManualFrame = false;

// Initialize manual override state on page load (e.g. for update mode)
jQuery(document).ready(function() {
    var initialLogo = jQuery('.logo-gallery .logo-item.active').find('input[type="hidden"]').val();
    var initialFrame = jQuery('.qr-frame-container .qr-frame-item.active').find('input[type="hidden"]').val();
    var initialTmpl = jQuery('.templates-list-item.active').find('input[type="hidden"]').val() || 'default';

    if (initialLogo && initialLogo !== (templateDefaultLogos[initialTmpl] || '')) {
        userHasManualLogo = true;
    }
    if (initialFrame && initialFrame !== (templateDefaultFrames[initialTmpl] || '')) {
        userHasManualFrame = true;
    }
});

// logo gallery
jQuery('.logo-gallery').on('click', '.logo-item', function () {
    userHasManualLogo = true;

    jQuery('.logo-item').not(this).find('input[type="hidden"][name$="_name"]').attr('name', function(_, attr) {
        return attr.replace('_name', '');
    });
    jQuery(this).siblings('.active').siblings().removeClass('active');
    jQuery(this).toggleClass('active').siblings().removeClass('active');

    if (jQuery(this).hasClass('active')) {
        jQuery(this).find('input[type="hidden"]').attr('name', 'default_logo_name');
    } else {
        jQuery(this).find('input[type="hidden"]').attr('name', 'default_logo');
    }
});

// select frame
jQuery('.qr-frame-container').on('click', '.qr-frame-item', function () {
    userHasManualFrame = true;

    jQuery('.qr-frame-item').not(this).find('input[type="hidden"][name$="_name"]').attr('name', function(_, attr) {
        return attr.replace('_name', '');
    });
    jQuery(this).siblings('.active').siblings().removeClass('active');
    jQuery(this).toggleClass('active').siblings().removeClass('active');

    if (jQuery(this).hasClass('active')) {
        jQuery(this).find('input[type="hidden"]').attr('name', 'frame_name');
    } else {
        jQuery(this).find('input[type="hidden"]').attr('name', 'frame');
    }
});

// select templates
jQuery('.templates-list-caption').on('click', '.templates-list-item', function () {

   jQuery('.templates-list-item').not(this).find('input[type="hidden"][name$="_name"]').attr('name', function(_, attr) {
       return attr.replace('_name', '');
   });
   jQuery(this).siblings('.active').siblings().removeClass('active');
   jQuery(this).toggleClass('active').siblings().removeClass('active');

   if (jQuery(this).hasClass('active')) {
       var templateName = jQuery(this).find('input[type="hidden"]').val();
       jQuery(this).find('input[type="hidden"]').attr('name', 'template_name');

       // Handle template default frames (only if user has not explicitly picked a frame)
       if (!userHasManualFrame) {
           var targetFrame = templateDefaultFrames[templateName] || '';
           jQuery('.qr-frame-container .qr-frame-item').each(function() {
               var hidden = jQuery(this).find('input[type="hidden"]');
               var val = hidden.val();
               if (targetFrame !== '' && val === targetFrame) {
                   jQuery(this).addClass('active');
                   hidden.attr('name', 'frame_name');
               } else {
                   jQuery(this).removeClass('active');
                   hidden.attr('name', 'frame');
               }
           });
       }

       // Handle template default logos (only if user has not explicitly picked a logo)
       if (!userHasManualLogo) {
           var targetLogo = templateDefaultLogos[templateName] || '';
           jQuery('.logo-gallery .logo-item').each(function() {
               var hidden = jQuery(this).find('input[type="hidden"]');
               var val = hidden.val();
               if (targetLogo !== '' && val === targetLogo) {
                   jQuery(this).addClass('active');
                   hidden.attr('name', 'default_logo_name');
               } else {
                   jQuery(this).removeClass('active');
                   hidden.attr('name', 'default_logo');
               }
           });
       }
   } else {
       jQuery(this).find('input[type="hidden"]').attr('name', 'template');
   }
});

var button = document.getElementById('slidenext');
button.onclick = function () {
    var templates_scroll = document.getElementById('templates_scroll');
    sideScroll(templates_scroll,'right',10,200,10);
};

var back = document.getElementById('slideprev');
back.onclick = function () {
    var templates_scroll = document.getElementById('templates_scroll');
    sideScroll(templates_scroll,'left',10,200,10);
};

function sideScroll(element, direction, speed, distance, step) {
    scrollAmount = 0;
    var slideTimer = setInterval(function(){
        if(direction == 'left'){
            element.scrollLeft -= step;
        } else {
            element.scrollLeft += step;
        }
        scrollAmount += step;

        // Check if at the beginning or end
        if (element.scrollLeft <= 0) {
            // Reached beginning, hide 'back' button
            document.getElementById('slideprev').style.display = 'none';
        } else {
            // Not at beginning, show 'back' button
            document.getElementById('slideprev').style.display = 'flex';
        }

        if (element.scrollLeft + element.clientWidth >= element.scrollWidth) {
            // Reached end, hide 'button' button
            document.getElementById('slidenext').style.display = 'none';
        } else {
            // Not at end, show 'button' button
            document.getElementById('slidenext').style.display = 'flex';
        }

        if(scrollAmount >= distance){
            window.clearInterval(slideTimer);
        }
    }, speed);
}
var templates_scroll = document.getElementById('templates_scroll');

templates_scroll.addEventListener('scroll', function() {
    if (templates_scroll.scrollLeft <= 0) {
        document.getElementById('slideprev').style.display = 'none';
    } else {
        document.getElementById('slideprev').style.display = 'flex';
    }

    if (templates_scroll.scrollLeft + templates_scroll.clientWidth >= templates_scroll.scrollWidth) {
        document.getElementById('slidenext').style.display = 'none';
    } else {
        document.getElementById('slidenext').style.display = 'flex';
    }
});

 // Scroll with Buttons (templates tab)