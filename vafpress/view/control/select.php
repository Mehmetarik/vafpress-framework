<tr class="vp-field vp-select<?php echo ($container_extra_classes) ? ' ' . $container_extra_classes : '' ?>" <?php echo VP_Util_Text::print_if_exists($validation, 'data-vp-validation="%s"'); ?> id="<?php echo $name; ?>">
	<td class="label">
		<label>
			<?php echo $label; ?>
			<?php VP_Util_Text::print_if_exists($description, '<div class="description">%s</div>'); ?>
		</label>
	</td>
	<td class="fields">
		<div class="input">
			<select name="<?php echo $name; ?>" class="vp-js-chosen">
				<?php foreach ($items as $item): ?>
				<option <?php if($item->value == $value) echo "selected" ?> value="<?php echo $item->value; ?>"><?php echo $item->label; ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<div class="validation-msgs"><ul></ul></div>
	</td>
</tr>