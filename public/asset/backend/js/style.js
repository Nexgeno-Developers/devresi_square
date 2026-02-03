$(document).ready(function () {
    // Function to check if the device is mobile
    function is_mobile() {
        return (
            /Mobi|Android/i.test(navigator.userAgent) || $(window).width() < 768
        );
    }

    $(".hide-menu").click(function (e) {
        e.preventDefault(),
            $("body").hasClass("hide-sidebar")
                ? $("body").removeClass("hide-sidebar").addClass("show-sidebar")
                : $("body")
                      .removeClass("show-sidebar")
                      .addClass("hide-sidebar");
    }),
        is_mobile() &&
            content_wrapper.on("click", function () {
                $("body").hasClass("show-sidebar") && $(".hide-menu").click();
            });
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
    const imageWrapper = input.closest(".media_wrapper").querySelector(".image_wrapper");

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
                removeMultipleImage(mediaImagesDiv, file, fileInput, selectedFiles);
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

