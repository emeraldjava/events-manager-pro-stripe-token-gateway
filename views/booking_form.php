<div class="form-row">
    <label><?php _e('Card Number', 'em-pro'); ?></label>
    <input type="text" size="15" value="4242424242424242" class="input" data-stripe="number"/>
</div>
<div class="form-row">
    <label><?php _e('Expiration Date', 'em-pro'); ?></label>
      <span class="expire_date" style="width:150px; display:inline;">
      <select data-stripe="exp-month" size="2" style="width:50px; display:inline;" value="02">
              <?php
              for ($i = 1; $i <= 12; $i++) {
                  $m = $i > 9 ? $i : "0$i";
                  echo "<option>$m</option>";
              }
              ?>
          </select> /
      <select data-stripe="exp-year" size="4" style="width:100px; display:inline;" value="2017">
          <?php
          $year = date('Y', current_time('timestamp'));
          for ($i = $year; $i <= $year + 4; $i++) {
              echo "<option>$i</option>";
          }
          ?>
      </select>
      </span>
</div>
<div class="form-row">
    <label><?php _e('CCV', 'em-pro'); ?></label>
    <input type="text" size="4" value="100" class="input" data-stripe="cvc"/>
</div>