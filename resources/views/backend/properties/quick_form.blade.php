<!-- Quick Add Form -->
<h3>Quick Add Property</h3>
<form action="{{ route('admin.properties.store') }}" method="POST">
    @csrf
    <input type="text" name="prop_name" placeholder="Property Name" required>
    <input type="text" name="line_1" placeholder="Line 1" required>
    <input type="text" name="line_2" placeholder="Line 2">
    <input type="text" name="city" placeholder="City" required>
    <input type="text" name="country" placeholder="Country" required>
    <input type="text" name="postcode" placeholder="Postcode" required>

    <div>
        <label>Property Type:</label>
        <input type="radio" name="property_type" value="sales"> Sales
        <input type="radio" name="property_type" value="lettings"> Lettings
        <input type="radio" name="property_type" value="both"> Both
    </div>

    <div>
        <label>Transaction Type:</label>
        <input type="radio" name="transaction_type" value="residential"> Residential
        <input type="radio" name="transaction_type" value="commercial"> Commercial
    </div>

    <div>
        <label>Specific Property Type:</label>
        <input type="radio" name="specific_property_type" value="apartment"> Apartment
        <input type="radio" name="specific_property_type" value="flat"> Flat
        <input type="radio" name="specific_property_type" value="bungalow"> Bungalow
        <input type="radio" name="specific_property_type" value="house"> House
    </div>

    <div>
        <label>Bedrooms:</label>
        <input type="radio" name="bedroom" value="1"> 1
        <input type="radio" name="bedroom" value="2"> 2
        <input type="radio" name="bedroom" value="3"> 3
        <input type="radio" name="bedroom" value="4"> 4
        <input type="radio" name="bedroom" value="5"> 5
        <input type="radio" name="bedroom" value="6+"> 6+
    </div>

    <div>
        <label>Bathrooms:</label>
        <input type="radio" name="bathroom" value="1"> 1
        <input type="radio" name="bathroom" value="2"> 2
        <input type="radio" name="bathroom" value="3"> 3
        <input type="radio" name="bathroom" value="4"> 4
        <input type="radio" name="bathroom" value="5"> 5
        <input type="radio" name="bathroom" value="6+"> 6+
    </div>

    <div>
        <label>Receptions:</label>
        <input type="radio" name="reception" value="1"> 1
        <input type="radio" name="reception" value="2"> 2
        <input type="radio" name="reception" value="3"> 3
        <input type="radio" name="reception" value="4"> 4
        <input type="radio" name="reception" value="5"> 5
        <input type="radio" name="reception" value="6+"> 6+
    </div>

    <input type="text" name="price" placeholder="Price" required>
    <input type="date" name="available_from" required>
    <button type="submit" class="btn btn_secondary">Add Property</button>
</form>
<div class="alert alert-warning mt-2">Remember to fill in all details later!</div>