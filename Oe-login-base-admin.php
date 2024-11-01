<?php


global $OpenErpLogin;

if( isset( $_GET[ 'tab' ] ) ) {
    $active_tab = $_GET[ 'tab' ];
} else {
	$active_tab = 'simple';
}
?>
<div class="wrap">

    <div id="icon-themes" class="icon32"></div>
    <h2>OpenErp V7 Login Settings</h2>

    <h2 class="nav-tab-wrapper">
        <a href="<?php echo add_query_arg( array('tab' => 'simple'), $_SERVER['REQUEST_URI'] ); ?>"
            class="nav-tab <?php echo $active_tab == 'simple' ? 'nav-tab-active' : ''; ?>">Simple</a>
        <a href="<?php echo add_query_arg( array('tab' => 'help'), $_SERVER['REQUEST_URI'] ); ?>"
            class="nav-tab <?php echo $active_tab == 'help' ? 'nav-tab-active' : ''; ?>">Help</a>
    </h2>

    <form method="post" action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>">
    	<?php wp_nonce_field( 'save_oe_settings','save_the_oe' ); ?>
    	<?php if( $active_tab == "simple" ): ?>
    	<h3>Required</h3>
    	<p>These are the most basic settings you must configure. Without these, you won't be able to use OpenErp Login supervisor.</p>
    	<table class="form-table">
			<tbody>
				<tr>
					<th scope="row" valign="top">Enable OpenERP Authentication</th>
					<td>
						<input type="hidden" name="<?php echo $this->get_field_name('enabled'); ?>" value="false" />
						<label><input type="checkbox" name="<?php echo $this->get_field_name('enabled'); ?>" value="true" <?php if( str_true($this->get_setting('enabled')) ) echo "checked"; ?> /> (Required) Enable OpenErp login authentication for WordPress. (this one is kind of important)</label><br/>
					</td>
	    		</tr>
				<tr>
					<th scope="row" valign="top">AutomaticCreate user</th>
					<td>
						<input type="hidden" name="<?php echo $this->get_field_name('create_users'); ?>" value="false" />
						<label><input type="checkbox" name="<?php echo $this->get_field_name('create_users'); ?>" value="true" <?php if( str_true($this->get_setting('create_users')) ) echo "checked"; ?> />(Required) Enable OpenErp login create WordPres user</label><br/>

					</td>
	    		</tr>
                <tr>
					<th scope="row" valign="top">New User Role</th>
					<td>
						<select name="<?php echo $this->get_field_name('role'); ?>">
							<?php wp_dropdown_roles( strtolower($this->get_setting('role')) ); ?>
						</select></br>
                        <label>(Required) Role of user</label>
					</td>
				</tr>
	    		<tr>
					<th scope="row" valign="top">Server</th>
					<td>
						<input type="text" name="<?php echo $this->get_field_name('oe_server'); ?>"
                        value="<?php echo $OpenErpLogin->get_setting('oe_server'); ?>" /><br/>
						(Required) insert server and rpc address. Example: http://localhost:8069/xmlrpc/
					</td>
				</tr>
				<tr>
					<th scope="row" valign="top">Database Name</th>
					<td>
						<input type="text" name="<?php echo $this->get_field_name('oe_database'); ?>"
                        value="<?php echo $OpenErpLogin->get_setting('oe_database'); ?>" />
						<br/>
						(Required) Example: mytestdb
					</td>
				</tr>
				<tr>
					<th scope="row" valign="top">Mail Domain</th>
					<td>
						<input type="text" name="<?php echo $this->get_field_name('m_domain'); ?>"
                        value="<?php echo $OpenErpLogin->get_setting('m_domain'); ?>" />
						<br/>
						(Optional) if email field in openerp is not compiled and the nickname is not an email address<br/>
                        this field is used to create an email address<br/>
                        Example:if this field is <b> @gmail.com </b>  therefore <b>nikname@gmail.com</b>
					</td>
				</tr>
			</tbody>
    	</table>
    	<p><input class="button-primary" type="submit" value="Save Settings" /></p>
    	<?php else: ?>
		<h3>Help</h3>
		<p>Here's a brief primer on how to effectively use and test Simple OpenErp Login.</p>
		<h4>Testing</h4>
		<p>The most effective way to test logins is to use two browsers. In other words, keep WordPress Admin open in Chrome, and use Firefox to try logging in. This will give you real time feedback on your settings and prevent you from inadvertently locking yourself out.</p>
		<h4>Which raises the question, what happens if I get locked out?</h4>
		<p>If you accidentally lock yourself out, the easiest way to get back in is to rename <strong><?php echo plugin_dir_path(__FILE__); ?></strong> to something else and then refresh. WordPress will detect the change and disable OpenErp Login You can then rename the folder back to its previous name.</p>
    	<?php endif; ?>
    </form>
</div>

