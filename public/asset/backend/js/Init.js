toastr.options = {
    showHideTransition: "plain",
    closeButton: true,
    newestOnTop: false,
    progressBar: true,
    positionClass: "toast-top-right",
    preventDuplicates: false,
    onclick: null,
    showDuration: "300",
    hideDuration: "500",
    timeOut: "7000",
    extendedTimeOut: "1000",
    showEasing: "swing",
    hideEasing: "linear",
    showMethod: "fadeIn",
    hideMethod: "fadeOut",
};

//bootstarp modals
function largeModal(url, header) {
    $("#largeModal .modal-body").html("Loading...");
    $("#largeModal .modal-title").html("Loading...");

    $("#largeModal").modal("show");
    $.ajax({
        url: url,
        success: function (response) {
            $("#largeModal .modal-body").html(response);
            $("#largeModal .modal-title").html(header);
        },
    });
}

function smallModal(url, header) {
    $("#smallModal .modal-body").html("Loading...");
    $("#smallModal .modal-title").html("Loading...");

    $("#smallModal").modal("show");
    $.ajax({
        url: url,
        success: function (response) {
            $("#smallModal .modal-body").html(response);
            $("#smallModal .modal-title").html(header);
        },
    });
}

function confirmModal(delete_url, param) {
    $("#confirmModal").modal("show");
    callBackFunction = param;
    document.getElementById("delete_form").setAttribute("action", delete_url);
}

$(".ajaxDeleteForm").submit(function (e) {
    var form = $(this);
    ajaxSubmit(e, form, callBackFunction);
});

function closeModel() {
    //$('.modal .modal-body').html('');
    //$('.modal .modal-title').html('');
}

function closeConfirmModel() {
    $("#confirmModal").modal("hide");
}

//jquery validator
function initValidate(selector) {
    $(selector).validate({
        errorElement: "div",
        errorPlacement: function (error, element) {
            error.addClass("invalid-feedback");
            element.closest(".form-group").append(error);
        },
        highlight: function (element, errorClass, validClass) {
            $(element).addClass("is-invalid");
        },
        unhighlight: function (element, errorClass, validClass) {
            $(element).removeClass("is-invalid");
        },
    });
    // Return whether the form is valid
    return $(selector).valid();
}

//select2
function initSelect2(selector) {
    $(selector).select2();
}

function initSelect3(selector) {
    $(selector).select2({
        minimumInputLength: 3,
        minimumResultsForSearch: 0,
        placeholder: 'Start typing at least 3 characters',
        language: {
            inputTooShort: function () {
                return "Please enter 3 or more characters";
            }
        }
    });
}


//Form Submition
function ajaxSubmit(e, form, callBackFunction) {
    if (form.valid()) {
        e.preventDefault();

        var btn = $(form).find('button[type="submit"]');
        var btn_text = $(btn).html();
        $(btn).html('<i class="ri-refresh-line"></i>');
        $(btn).css("opacity", "0.7");
        $(btn).css("pointer-events", "none");

        var action = form.attr("action");
        var form = e.target;
        var data = new FormData(form);
        $.ajax({
            type: "POST",
            url: action,
            processData: false,
            contentType: false,
            dataType: "json",
            data: data,
            success: function (response) {
                $(btn).html(btn_text);
                $(btn).css("opacity", "1");
                $(btn).css("pointer-events", "inherit");

                if (response.status) {
                    // Command: toastr["success"](
                    //     response.notification,
                    //     "Success"
                    // );
                    AIZ.plugins.notify('success', response.message);
                    callBackFunction(response);
                } else {
                    if (typeof response.notification === "object") {
                        var errors = "";
                        $.each(response.notification, function (key, msg) {
                            errors +=
                                "<div>" + (key + 1) + ". " + msg + "</div>";
                        });
                        Command: toastr["error"](errors, "Alert");
                    } else {
                        Command: toastr["error"](
                            response.notification,
                            "Alert"
                        );
                    }
                }
            },
        });
    } else {
        toastr.error("Please make sure to fill all the necessary fields");
    }
}

//trumbowyg Editor
function initTrumbowyg(target) {
    $(target).trumbowyg({
        btnsDef: {
            // Create a new dropdown
            image: {
                dropdown: ["insertImage", "upload"],
                ico: "insertImage",
            },
            // Define the heading button with different levels
            heading: {
                dropdown: ["h1", "h2", "h3", "h4", "h5", "h6"],
                ico: "pencil", // You can use an appropriate icon
            },
        },
        // Redefine the button pane
        btns: [
            ["viewHTML"],
            ["formatting"],
            ["strong", "em", "del"],
            ["superscript", "subscript"],
            ["link"],
            ["image"], // Our fresh created dropdown
            ['noembed'],
            ["table"],
            ["justifyLeft", "justifyCenter", "justifyRight", "justifyFull"],
            ["unorderedList", "orderedList"],
            ["horizontalRule"],
            ["removeformat"],
            ["fullscreen"],
        ],
        plugins: {
            // Add imagur parameters to upload plugin for demo purposes
            upload: {
                serverPath:
                    $("#baseUrl").attr("href") + "/backend/trumbowyg/upload",
                fileFieldName: "image",
                headers: {},
                urlPropertyName: "file",
            },
            resizimg: true,
        },
    });
    // $(target).css("height", 200);
    // $(".trumbowyg-editor").css("min-height", 200);
}
function destroyTrumbowyg(target) {
    $(target).trumbowyg("destroy");
}

function openImageModal(imageSrc) {
    $("#previewImage").attr("src", imageSrc); // Set image source
    $("#imagePreviewModal").modal("show"); // Show modal
}

// Hide modal when close button is clicked
$("#closeModalBtn").click(function () {
    $("#imagePreviewModal").modal("hide");
});

// Hide modal when clicking outside modal content
$(document).on("click", function (event) {
    if (!$(event.target).closest(".modal-content").length) {
        $("#imagePreviewModal").modal("hide");
    }
});
$(document).on('click', '.toggle-link', function() {
    const target = $(this).data('target');
    const shortText = $('#' + target + '_short');
    const fullText = $('#' + target + '_full');

    if (fullText.hasClass('d-none')) {
        shortText.addClass('d-none');
        fullText.removeClass('d-none');
        $(this).text('Show Less');
    } else {
        shortText.removeClass('d-none');
        fullText.addClass('d-none');
        $(this).text('Show More');
    }
});

function toggleDescriptions() {
    let propertyType = $('input[name="property_type"]:checked').val();
    
    // Show/hide based on selected type
    if (propertyType === 'sales') {
        $('.sales_description').show();
        $('.lettings_description').hide();
    } else if (propertyType === 'lettings') {
        $('.sales_description').hide();
        $('.lettings_description').show();
    } else if (propertyType === 'both') {
        $('.sales_description').show();
        $('.lettings_description').show();
    } else {
        $('.sales_description, .lettings_description').hide();
    }
}

toggleDescriptions();

$(document).on('change', 'input[name="property_type"]', function() {
    toggleDescriptions();
});

function toggleEPCRating() {
    if ($('input[name="epc_required"]:checked').val() === '1') {
        $('#epc_rating_container').show();
    } else {
        $('#epc_rating_container').hide();
    }
}
toggleEPCRating();
$(document).on('change', 'input[name="epc_required"]', function() {
    toggleEPCRating();
});

/**
 * Initialize “Other Religious Places” add/remove + reindexing.
 *
 * @param {string} wrapperSel   Selector for the container (e.g. '#places-wrapper')
 * @param {string} addBtnSel    Selector for the “Add More” button (e.g. '#add-place-btn')
 */
// function initPlaces(wrapperSel, addBtnSel) {
//     const $wrapper = $(wrapperSel);
//     const $addBtn   = $(addBtnSel);
  
//     // 1️⃣ Row template with a placeholder __IDX__
//     const rowTpl = `
//       <div class="input-group mb-2 place-entry">
//         <input 
//           type="text" 
//           name="nearest_places[__IDX__][name]" 
//           class="form-control" 
//           placeholder="Place name" 
//           required
//         >
//         <input 
//           type="number" 
//           name="nearest_places[__IDX__][distance]" 
//           class="form-control" 
//           placeholder="Distance (KM)" 
//           required
//         >
//         <button class="btn btn-danger remove-place" type="button">-</button>
//       </div>`;
  
//     // 2️⃣ Re-index every .place-entry in the wrapper
//     function reIndex() {
//       $wrapper.find('.place-entry').each(function(i, el) {
//         const $el = $(el);
//         $el.find('input[name$="[name]"]')
//            .attr('name', `nearest_places[${i}][name]`);
//         $el.find('input[name$="[distance]"]')
//            .attr('name', `nearest_places[${i}][distance]`);
//       });
//     }
  
//     // 3️⃣ Add Row handler
//     $addBtn.off('click.place').on('click.place', () => {
//       // append with a dummy index, then reindex
//       $wrapper.append(rowTpl.replace(/__IDX__/g, $wrapper.children().length));
//       reIndex();
//     });
  
//     // 4️⃣ Remove Row handler (delegated)
//     $wrapper.off('click.place', '.remove-place')
//             .on('click.place', '.remove-place', function() {
//       $(this).closest('.place-entry').remove();
//       reIndex();
//     });
  
//     // 5️⃣ Initial reIndex to clean up any server-rendered rows
//     reIndex();
//   }
  
//   // — call on DOM ready —
//   $(function(){
//     initPlaces('#places-wrapper', '#add-place-btn');
//   });

// //can use in anywhere initPlaces('#places-wrapper', '#add-place-btn');
  