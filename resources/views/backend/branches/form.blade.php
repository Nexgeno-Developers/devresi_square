<div class="form-group">
    <label for="name">Branch Name</label>
    <input type="text" name="name" id="name" class="form-control"
        value="{{ old('name', $branch->name ?? '') }}" required>
</div>

<div class="form-group">
    <label for="address">Address</label>
    <input type="text" name="address" id="address" class="form-control"
        value="{{ old('address', $branch->address ?? '') }}" >
</div>

<div class="form-group">
    <label for="city">City</label>
    <input type="text" name="city" id="city" class="form-control"
        value="{{ old('city', $branch->city ?? '') }}" >
</div>

<div class="form-group">
    <label for="postcode">Postcode</label>
    <input type="text" name="postcode" id="postcode" class="form-control"
        value="{{ old('postcode', $branch->postcode ?? '') }}" >
</div>

<div class="form-group">
    <label for="user_email">User Email</label>
    <input type="email" name="user_email" id="user_email" class="form-control"
        value="{{ old('user_email', $branch->user_email ?? '') }}" >
</div>

<div class="form-group">
    <label for="user_phone">User Phone</label>
    <input type="text" name="user_phone" id="user_phone" class="form-control"
        value="{{ old('user_phone', $branch->user_phone ?? '') }}" >
</div>

<button type="submit" class="btn btn_secondary">{{ $buttonText ?? 'Save' }}</button>
