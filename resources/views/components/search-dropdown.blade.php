<div class="d-flex position-relative">
    <input type="text" id="{{ $name }}" placeholder="{{ $placeholder }}" class="form-control searchInput">
    <ul class="custom-dropdown"></ul>
</div>

<!-- Hidden input to store selected category (with data-id) -->
<input type="hidden" name="{{ $name }}" data-id="" id="selectedCategory" class="selectedCategory">

<!-- Radio Buttons will be shown here -->
<div id="previewContainer" class="mt-2"></div>

<style>
.custom-dropdown {
    position: absolute;
    top: 100%;
    left: 0;
    width: 100%;
    max-height: 200px;
    overflow-y: auto;
    background: #fff;
    border: 1px solid #ccc;
    border-radius: 4px;
    z-index: 1000;
    display: none;
    padding: 0;
    list-style: none;
}

.custom-dropdown li {
    padding: 8px;
    cursor: pointer;
}

.custom-dropdown li:hover, .custom-dropdown li.active {
    background: #007bff;
    color: #fff;
}
</style>

@push('domready.scripts')
<script>
$(document).ready(function () {
    let repairCategories = [];
    let selectedIndex = -1;
    let route = "{{ $route }}";

    // Fetch categories from the backend
    $.get(route, function (data) {
        repairCategories = data;
    });

// Update this section to correctly handle category object
$(".searchInput").on("input", function () {
    let input = $(this).val().toLowerCase();
    let dropdown = $(this).siblings(".custom-dropdown");

    dropdown.empty().hide();
    selectedIndex = -1;

    if (input.length === 0) return;

    // Filter categories based on the 'name' property (if category is an object with a 'name' field)
    let filtered = repairCategories
        .filter(category => category.name.toLowerCase().includes(input)) // Use category.name
        .slice(0, 10);

    if (filtered.length > 0) {
        $.each(filtered, function (index, category) {
            dropdown.append(`<li data-value="${category.name}" data-id="${category.id}">${category.name}</li>`);
        });

        dropdown.show();
    }
});


    // Handle keyboard navigation and selection
    $(".searchInput").on("keydown", function (e) {
        let dropdown = $(this).siblings(".custom-dropdown");
        let items = dropdown.find("li");

        if (dropdown.is(":visible")) {
            if (e.key === "ArrowDown") {
                e.preventDefault();
                selectedIndex = (selectedIndex + 1) % items.length;
            } else if (e.key === "ArrowUp") {
                e.preventDefault();
                selectedIndex = (selectedIndex - 1 + items.length) % items.length;
            } else if (e.key === "Enter") {
                e.preventDefault();
                if (selectedIndex !== -1) {
                    let selectedCategory = items.eq(selectedIndex).data("value");
                    $(this).val(selectedCategory);
                    dropdown.hide();
                    $(this).siblings(".selectedCategory").val(selectedCategory);  // Update the hidden input
                    showRadioButtons(selectedCategory);
                }
            }

            items.removeClass("active");
            if (selectedIndex !== -1) {
                items.eq(selectedIndex).addClass("active");
            }
        }
    });

    // Handle clicking an item in the dropdown
    $(document).on("click", ".custom-dropdown li", function () {
        let selectedCategory = $(this).data("value");
        let selectedId = $(this).data("id");

        // Find the corresponding category object from the repairCategories array using selectedId
        let category = repairCategories.find(item => item.id === selectedId);

        // Update the input field and hidden input
        $(this).closest(".d-flex").find(".searchInput").val(category.name);
        $(this).closest(".d-flex").find("#selectedCategory").val(category.id); // Update the hidden input with the category ID
        $(this).parent().hide();

        showRadioButtons(category);  // Pass the full category object
    });


    // Hide dropdown if clicking outside
    $(document).click(function (event) {
        if (!$(event.target).closest(".d-flex").length) {
            $(".custom-dropdown").hide();
        }
    });

    // Display the radio button and form when category is selected
    function showRadioButtons(category) {
        $("#previewContainer").html(`
            <label>
                <input type="radio" name="selected_category" value="${category.id}" checked> ${category.name}
            </label>
        `);

        // Update the hidden input with category ID and set the data-id
        $("#selectedCategory")
            .val(category.id)                // Set the value of the hidden input to the category ID
            .attr("data-id", category.id);   // Set the data-id attribute to the category ID

        // Show the form

        // Show the form
        $("#repair-form").removeClass("d-none");
    }
});
</script>
@endpush
