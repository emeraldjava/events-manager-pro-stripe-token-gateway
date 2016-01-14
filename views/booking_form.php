<div class="form-inline">
    <label><span>Card Number *</span></label>
    <input type="text" style="width:20em" maxlength="16" data-stripe="number" name="stripe_number"/>
</div>
<div class="form-inline">
    <div class="form-group" style="width: 100%">
        <label><span>Exp Month *</span></label>
        <input type="text" style="width:4em" class="form-control" maxlength="2" data-stripe="exp-month" name="stripe_exp_month" placeholder="MM"/>
    </div>
    </div>
<div class="form-inline">
    <div class="form-group" style="width: 100%">
        <label for="input2"><span>Year *</span></label>
        <input type="text" style="width:4em" class="form-control" maxlength="4" data-stripe="exp-year" name="stripe_exp_year" placeholder="YYYY"/>
    </div>
</div>
<div class="form-inline">
    <label><span><?php _e('CCV *', 'em-pro');?></label></span></label>
    <input type="text" maxlength="4" style="width:4em" data-stripe="cvc" name="stripe_cvc" placeholder="CVC"/>
</div>
<br/>