$(document).ready(function () {
    // Function to check if the device is mobile
    function is_mobile() {
        return (
            /Mobi|Android/i.test(navigator.userAgent) || $(window).width() < 992
        );
    }

    var $body = $("body");
    // The header hamburger (desktop) and the bottom bar's "More" tab (phone and tablet).
    var $headerToggle = $(".hide-menu");
    var $toggle = $headerToggle.add(".js-more-toggle");
    // Match the overlay breakpoint in aiz-main.css; below it the sidebar is a drawer.
    var overlayQuery = window.matchMedia("(max-width: 991px)");

    function setSidebar(open) {
        $body
            .toggleClass("show-sidebar", open)
            .toggleClass("hide-sidebar", !open);
        $toggle.attr("aria-expanded", open ? "true" : "false");
    }

    function sidebarOpen() {
        return $body.hasClass("show-sidebar");
    }

    // Phones and tablets start with the drawer closed, desktop with the rail open.
    setSidebar(!overlayQuery.matches);

    // Rotating a tablet or resizing the window crosses the breakpoint: reset to its default.
    var onBreakpointChange = function (e) {
        setSidebar(!e.matches);
    };
    if (overlayQuery.addEventListener) {
        overlayQuery.addEventListener("change", onBreakpointChange);
    } else if (overlayQuery.addListener) {
        overlayQuery.addListener(onBreakpointChange);
    }

    // The header button is a div, so it needs button semantics. "More" is a real button.
    $headerToggle.attr({
        role: "button",
        tabindex: "0",
        "aria-controls": "menu",
    });

    $toggle.click(function (e) {
        e.preventDefault();
        setSidebar(!sidebarOpen());
    });

    // Space and Enter work on the header button like a real button.
    $headerToggle.on("keydown", function (e) {
        if (e.key === "Enter" || e.key === " ") {
            e.preventDefault();
            setSidebar(!sidebarOpen());
        }
    });

    // Tapping the dimmed area closes the drawer.
    $(".backdrop").click(function (e) {
        e.preventDefault();
        if (overlayQuery.matches) {
            setSidebar(false);
        }
    });

    // Escape closes the drawer, and a tap on a real link closes it before the next page loads.
    $(document).on("keydown", function (e) {
        if (e.key === "Escape" && overlayQuery.matches && sidebarOpen()) {
            setSidebar(false);
            $toggle.filter(":visible").first().trigger("focus");
        }
    });
    $("#menu").on("click", "a[href]", function () {
        var href = $(this).attr("href") || "";
        var opensSubmenu = $(this).is('[data-bs-toggle="collapse"]');
        if (overlayQuery.matches && !opensSubmenu && href.charAt(0) !== "#") {
            setSidebar(false);
        }
    });

    // Define content_wrapper
    var content_wrapper = $(".content-wrapper");
    if (is_mobile()) {
        content_wrapper.on("click", function () {
            sidebarOpen() && setSidebar(false);
        });
    }
});

let selectedFiles = []; // Array to hold selected files

function previewImage(input) {
    const fileInput = input;
    const imagePreview = fileInput
        .closest(".mb-3")
        .querySelector(".image-preview");
    const uploadedImage = imagePreview.querySelector(".uploaded-image");

    if (fileInput.files && fileInput.files[0]) {
        const reader = new FileReader();

        reader.onload = function (e) {
            uploadedImage.src = e.target.result;
            imagePreview.style.display = "block";
        };

        reader.readAsDataURL(fileInput.files[0]);
    }
}

function removeImage(button) {
    const imagePreview = button.closest(".image-preview");
    const fileInput = imagePreview.previousElementSibling; // Previous sibling is the file input

    // Reset the file input
    fileInput.value = "";
    imagePreview.style.display = "none";
    imagePreview.querySelector(".uploaded-image").src = ""; // Clear the image preview
}

function previewMultipleImage(input) {
    const fileInput = input;
    const imageWrapper = input
        .closest(".media_wrapper")
        .querySelector(".image_wrapper");

    // Create a local array for each input to track selected files
    let selectedFiles = Array.from(fileInput.files);

    // Remove existing previews before adding new ones
    imageWrapper.innerHTML = ""; // Ensure we don't add duplicates

    selectedFiles.forEach((file) => {
        const reader = new FileReader();

        reader.onload = function (e) {
            // Create the container for each preview
            const mediaImagesDiv = document.createElement("div");
            mediaImagesDiv.className = "media_images";

            // Create the delete button
            const deleteButtonDiv = document.createElement("div");
            deleteButtonDiv.className = "delete_image";
            deleteButtonDiv.innerHTML = `
                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="currentColor" class="bi bi-x-lg" viewBox="0 0 16 16">
                        <path d="M2.146 2.854a.5.5 0 1 1 .708-.708L8 7.293l5.146-5.147a.5.5 0 0 1 .708.708L8.707 8l5.147 5.146a.5.5 0 0 1-.708.708L8 8.707l-5.146 5.147a.5.5 0 0 1-.708-.708L7.293 8z" />
                    </svg>`;
            deleteButtonDiv.onclick = function () {
                removeMultipleImage(
                    mediaImagesDiv,
                    file,
                    fileInput,
                    selectedFiles
                );
            };

            // Create the image element
            const img = document.createElement("img");
            img.src = e.target.result;
            img.alt = "Uploaded Image";

            // Append elements to the preview container
            mediaImagesDiv.appendChild(deleteButtonDiv);
            mediaImagesDiv.appendChild(img);
            imageWrapper.appendChild(mediaImagesDiv);
        };

        reader.readAsDataURL(file);
    });
}

function removeMultipleImage(mediaImagesDiv, file, input, selectedFiles) {
    const imageWrapper = mediaImagesDiv.parentElement;

    // Remove the selected preview element
    imageWrapper.removeChild(mediaImagesDiv);

    // Filter out the removed file from the selectedFiles array
    selectedFiles = selectedFiles.filter((f) => f !== file);

    // Update the file input to reflect the remaining files
    updateFileInput(input, selectedFiles);
}

function updateFileInput(input, selectedFiles) {
    const dataTransfer = new DataTransfer();

    // Add remaining files back to DataTransfer
    selectedFiles.forEach((file) => dataTransfer.items.add(file));

    // Set the file input's files to the new file list
    input.files = dataTransfer.files;
}

// Hide alert after click on it
$(".alert").click(function showAlert() {
    $(".alert")
        .fadeTo(1000, 500)
        .slideUp(500, function () {
            $(".alert").slideUp(500);
        });
});



// Wide data tables on phones: wrap any .table that isn't already inside a
// horizontal scroll container, so it scrolls within its card instead of being
// clipped by the page. Also catches tables injected later (tabs, modals, AJAX).
(function () {
    function wrapTables(root) {
        if (!root || !root.querySelectorAll) return;
        var tables = root.matches && root.matches("table.table") ? [root] : root.querySelectorAll("table.table");
        Array.prototype.forEach.call(tables, function (table) {
            if (table.closest(".table-responsive, .dataTables_wrapper, .rs-table-scroll")) return;
            for (var p = table.parentElement; p && p !== document.body; p = p.parentElement) {
                var ox = getComputedStyle(p).overflowX;
                if (ox === "auto" || ox === "scroll") return;
                if (p.id === "wrapper") break;
            }
            var wrap = document.createElement("div");
            wrap.className = "table-responsive rs-table-scroll";
            table.parentNode.insertBefore(wrap, table);
            wrap.appendChild(table);
        });
    }

    function start() {
        var scope = document.getElementById("wrapper") || document.body;
        wrapTables(scope);
        if (window.MutationObserver) {
            new MutationObserver(function (mutations) {
                mutations.forEach(function (m) {
                    Array.prototype.forEach.call(m.addedNodes, function (n) {
                        if (n.nodeType === 1) wrapTables(n);
                    });
                });
            }).observe(scope, { childList: true, subtree: true });
        }
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", start);
    } else {
        start();
    }
})();
