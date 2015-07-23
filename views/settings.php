<table class="form-table">
    <tbody>
    <tr valign="top">
        <th scope="row"><?php _e('Success Message', 'em-pro') ?></th>
        <td>
            <input type="text" name="_booking_feedback" value="<?php esc_attr_e(get_option('em_'. $this->gateway . "_booking_feedback" )); ?>" style='width: 40em;' /><br />
            <em><?php _e('The message that is shown to a user when a booking is successful and payment has been taken.','em-pro'); ?></em>
        </td>
    </tr>
    <tr valign="top">
        <th scope="row"><?php _e('Success Free Message', 'em-pro') ?></th>
        <td>
            <input type="text" name="_booking_feedback_free" value="<?php esc_attr_e(get_option('em_'. $this->gateway . "_booking_feedback_free" )); ?>" style='width: 40em;' /><br />
            <em><?php _e('If some cases if you allow a free ticket (e.g. pay at gate) as well as paid tickets, this message will be shown and the user will not be charged.','em-pro'); ?></em>
        </td>
    </tr>
    </tbody>
</table>
<h3><?php echo sprintf(__('%s Options','dbem'),'Stripe')?></h3>
<table class="form-table">
    <tbody>
    <tr valign="top">
        <th scope="row"><?php _e('Stripe Currency', 'em-pro') ?></th>
        <td><?php echo esc_html(get_option('dbem_bookings_currency','USD')); ?><br /><i><?php echo sprintf(__('Set your currency in the <a href="%s">settings</a> page.','dbem'),EM_ADMIN_URL.'&amp;page=events-manager-options#bookings'); ?></i></td>
    </tr>
    <tr valign="top">
        <th scope="row"><?php _e('Mode', 'em-pro'); ?></th>
        <td>
            <select name="_mode">
                <?php $selected = get_option('em_'.$this->gateway.'_mode'); ?>
                <option value="test" <?php echo ($selected == 'test') ? 'selected="selected"':''; ?>><?php _e('Test','emp-pro'); ?></option>
                <option value="live" <?php echo ($selected == 'live') ? 'selected="selected"':''; ?>><?php _e('Live','emp-pro'); ?></option>
            </select>
        </td>
    </tr>
    <tr valign="top">
        <th scope="row"><?php _e('Test Secret Key', 'emp-pro') ?></th>
        <td><input type="text" name="_test_secret_key" value="<?php esc_attr_e(get_option( 'em_'. $this->gateway . "_test_secret_key", "" )); ?>" style='width: 40em;' /></td>
    </tr>
    <tr valign="top">
        <th scope="row"><?php _e('Test Publishable Key', 'emp-pro') ?></th>
        <td><input type="text" name="_test_publishable_key" value="<?php esc_attr_e(get_option( 'em_'. $this->gateway . "_test_publishable_key", "" )); ?>" style='width: 40em;' /></td>
    </tr>
    <tr valign="top">
        <th scope="row"><?php _e('Live Secret Key', 'emp-pro') ?></th>
        <td><input type="text" name="_live_secret_key" value="<?php esc_attr_e(get_option( 'em_'. $this->gateway . "_live_secret_key", "" )); ?>" style='width: 40em;' /></td>
    </tr>
    <tr valign="top">
        <th scope="row"><?php _e('Live Publishable Key', 'emp-pro') ?></th>
        <td><input type="text" name="_live_publishable_key" value="<?php esc_attr_e(get_option( 'em_'. $this->gateway . "_live_publishable_key", "" )); ?>" style='width: 40em;' /></td>
    </tr>
    </tbody>
</table>