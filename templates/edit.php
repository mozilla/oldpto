<form method="post">
	<input type="hidden" name="id" value="<?php echo $aFormValues['id'] ?>" />
	<input type="hidden" name="hours_daily" value="<?php echo urlencode($aFormValues['hours_daily']) ?>" />
	
    <p>Hello, <?php echo email_to_alias($notifier_email) ?>. 
    <?php echo $is_editing ? 'Edit' : 'Submit' ?> your PTO notification here. 
    (<a href="https://mana.mozilla.org/wiki/pages/viewpage.action?pageId=33100583">Learn about PTO</a>)</p>

	<?php
		if ($aErrors) {
			print '<div class="errors">';
			print '<b>Please fix the following errors:</b><br />';
			foreach ($aErrors as $sError) {
				print '&nbsp; - '.$sError.'<br />';
			}
			print '</div>';
		}
	?>

    <table>
		<tbody>
		    <tr>
		      <td><label for="start">Start</label></td>
		      <td>
		        <input type="text" id="start" name="start" size="10" autocomplete="off" value="<?php echo $aFormValues['start'] ?>" />
		      </td>
		    </tr>
		    <tr>
		      <td><label for="end">End</label></td>
		      <td>
		        <input type="text" id="end" name="end" size="10" autocomplete="off" value="<?php echo $aFormValues['end'] ?>" />
		      </td>
		    </tr>
		    <tr>
		<?php $for_this_edit = $is_editing ? "<br />(for this edit)" : ''; ?>
		      <td><label for="people">People to Notify</label><?php echo $for_this_edit ?></td>
		      <td>
		        <?php echo htmlentities(implode(", ", $notified_people)) ?><br />
		        <textarea name="people" cols="80" rows="2"><?php echo $aFormValues['people'] ?></textarea><br />
		        <input type="checkbox" name="cc" id="cc" value="1" />
		        <label for="cc">CC me</label>
		      </td>
		    </tr>
		    <tr>
		      <td><label for="details">Details</label><br />(optional)</td>
		      <td>
		        <textarea name="details" cols="80" rows="10"><?php echo $aFormValues['details'] ?></textarea>
		      </td>
		    </tr>
    	</tbody>
	</table>
    <input type="submit" value="Next &raquo;" />
	
</form>

<style>
	.errors {
		margin: 5px 0 20px 0;
		border: 1px solid red;
		padding: 10px;
		background-color: #FFCCCC;
		color: #CC0000;
	}
		.errors b {
			font-weight: bold;
			font-size: 16px;
		}
</style>

<script>
	$(document).ready(function() {
		$ = jQuery;
		$('#start, #end').datepicker({
	    	onClose: function() {  }
	    });
	});
</script>

	
