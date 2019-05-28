<form id="form-add-item" name="form-add-item" autocomplete="off" >
    <div class="form-group">
        <label for="first_name">First name</label>
        <input class="form-control" type="text" name="first_name" id="first_name" data-lpignore="true">
    </div>
    <div class="form-group">
        <label for="last_name">Last name</label>
        <input class="form-control" autocomplete="off" type="text" name="last_name" id="last_name" data-lpignore="true">
    </div>
    <div class="form-group">
        <label for="email">Email</label>
        <input class="form-control" autocomplete="off" type="email" name="email" id="email" data-lpignore="true">
    </div>
    <div class="form-group">
        <label for="country_iso_code">Country</label>
        <select id="country_iso_code" class="form-control" name="country_iso_code" required autofocus data-lpignore="true">
            <option value="">--SELECT A COUNTRY --</option>
            @foreach($countries as $country)
                <option value="{!! $country->getAlpha2() !!}">{!! $country->getName() !!}</option>
            @endforeach
        </select>
    </div>
    <div class="form-group">
        <label class="control-label" for="groups">Groups&nbsp;<span class="glyphicon glyphicon-info-sign accordion-toggle" aria-hidden="true" title=""></span></label>
        <input type="text" autocomplete="off"  class="form-control" name="groups" id="groups" value="" data-lpignore="true">
    </div>
    <div class="form-group password-container">
        <input type="password" class="form-control" id="password" name="password" placeholder="Password" autocomplete="new-password" data-lpignore="true">
    </div>
    <div class="form-group password-container">
        <input type="password" class="form-control" id="password-confirm" name="password_confirmation" placeholder="Confirm Password" autocomplete="new-password" data-lpignore="true">
    </div>
    <div class="checkbox">
        <label>
            <input type="checkbox" id="active" name="active"/>&nbsp;Is Active?
        </label>
    </div>
    <div class="checkbox">
        <label>
            <input type="checkbox" id="email_verified" name="email_verified"/>&nbsp;Email Verified?
        </label>
    </div>
</form>