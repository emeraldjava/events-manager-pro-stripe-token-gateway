<p class="em-bookings-form-gateway-cardno">
    <label><?php _e('Card Number', 'em-pro'); ?></label>
    <input type="text" size="15" name="stripe_card_num" value="" class="input"/>
</p>
<p class="em-bookings-form-gateway-expiry">
    <label><?php _e('Expiration Date', 'em-pro'); ?></label>
      <span class="expire_date"><select name="stripe_exp_date_month" style="width:150px; display:inline;">
              <?php
              for ($i = 1; $i <= 12; $i++) {
                  $m = $i > 9 ? $i : "0$i";
                  echo "<option>$m</option>";
              }
              ?>
          </select> /
      <select name="stripe_exp_date_year" style="width:150px; display:inline;">
          <?php
          $year = date('Y', current_time('timestamp'));
          for ($i = $year; $i <= $year + 10; $i++) {
              echo "<option>$i</option>";
          }
          ?>
      </select></span>
</p>
<p class="em-bookings-form-ccv">
    <label><?php _e('CCV', 'em-pro'); ?></label>
    <input type="text" size="4" name="stripe_card_code" value="" class="input"/>
</p>