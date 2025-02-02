<!-- Begin toolbar -->
<div class="toolbar">
	<div class="toolbar-inner">
		<div class="toolbar-buttons">
			<a class="button button-secondary button-back js-button-back" href="/list/dns/?domain=<?= htmlentities(trim($v_domain, "'")) ?>&token=<?= $_SESSION["token"] ?>">
				<i class="fas fa-arrow-left icon-blue"></i><?= _("Back") ?>
			</a>
		</div>
		<div class="toolbar-buttons">
			<button type="submit" class="button" form="main-form">
				<i class="fas fa-floppy-disk icon-purple"></i><?= _("Save") ?>
			</button>
		</div>
	</div>
</div>
<!-- End toolbar -->

<div class="container">

	<form id="main-form" name="v_edit_dns_rec" method="post" class="<?= $v_status ?>">
		<input type="hidden" name="token" value="<?= $_SESSION["token"] ?>">
		<input type="hidden" name="save" value="save">

		<div class="form-container">
			<h1 class="u-mb20"><?= _("Edit DNS Record") ?></h1>
			<?php show_alert_message($_SESSION); ?>
			<div class="u-mb10">
				<label for="v_domain" class="form-label"><?= _("Domain") ?></label>
				<input type="text" class="form-control js-dns-record-domain" name="v_domain" id="v_domain" value="<?= htmlentities(trim($v_domain, "'")) ?>" disabled>
				<input type="hidden" name="v_domain" value="<?= htmlentities(trim($v_domain, "'")) ?>">
			</div>
			<div class="u-mb10">
				<label for="v_rec" class="form-label"><?= _("Record") ?></label>
				<input type="text" class="form-control js-dns-record-input" name="v_rec" id="v_rec" value="<?= htmlentities(trim($v_rec, "'")) ?>">
				<input type="hidden" name="v_record_id" value="<?= htmlentities(trim($v_record_id, "'")) ?>">
				<small class="hint"></small>
			</div>
			<div class="u-mb10">
				<label for="v_type" class="form-label"><?= _("Type") ?></label>
				<select class="form-select" name="v_type" id="v_type">
					<option value="A" <?php if ($v_type == 'A') echo "selected"; ?>>A</option>
					<option value="AAAA" <?php if ($v_type == 'AAAA') echo "selected"; ?>>AAAA</option>
					<option value="CAA" <?php if ($v_type == 'CAA') echo "selected"; ?>>CAA</option>
					<option value="CNAME" <?php if ($v_type == 'CNAME') echo "selected"; ?>>CNAME</option>
					<option value="DNSKEY" <?php if ($v_type == 'DNSKEY') echo "selected"; ?>>DNSKEY</option>
					<option value="DS" <?php if ($v_type == 'DS') echo "selected"; ?>>DS</option>
					<option value="IPSECKEY" <?php if ($v_type == 'IPSECKEY') echo "selected"; ?>>IPSECKEY</option>
					<option value="KEY" <?php if ($v_type == 'KEY') echo "selected"; ?>>KEY</option>
					<option value="MX" <?php if ($v_type == 'MX') echo "selected"; ?>>MX</option>
					<option value="NS" <?php if ($v_type == 'NS') echo "selected"; ?>>NS</option>
					<option value="PTR" <?php if ($v_type == 'PTR') echo "selected"; ?>>PTR</option>
					<option value="SPF" <?php if ($v_type == 'SPF') echo "selected"; ?>>SPF</option>
					<option value="SRV" <?php if ($v_type == 'SRV') echo "selected"; ?>>SRV</option>
					<option value="TLSA" <?php if ($v_type == 'TLSA') echo "selected"; ?>>TLSA</option>
					<option value="TXT" <?php if ($v_type == 'TXT') echo "selected"; ?>>TXT</option>
				</select>
			</div>
			<div class="u-mb10">
				<label for="v_val" class="form-label"><?= _("IP or Value") ?></label>
				<div class="u-pos-relative">
					<select class="form-select" tabindex="-1" onchange="this.nextElementSibling.value=this.value">
						<option value="">&nbsp;</option>
						<?php
							foreach ($v_ips as $ip => $value) {
								$display_ip = empty($value['NAT']) ? $ip : "{$value['NAT']}";
								echo "<option value='{$display_ip}'>" . htmlentities($display_ip) . "</option>\n";
							}
						?>
					</select>
					<input type="text" class="form-control list-editor" name="v_val" id="v_val" value="<?= htmlentities(trim($v_val, "'")) ?>">
				</div>
			</div>
			<div class="u-mb10">
				<label for="v_priority" class="form-label">
					<?= _("Priority") ?> <span class="optional">(<?= _("Optional") ?>)</span>
				</label>
				<input type="text" class="form-control" name="v_priority" id="v_priority" value="<?= htmlentities(trim($v_priority, "'")) ?>">
			</div>
			<div class="u-mb10">
				<label for="v_ttl" class="form-label">
					<?= _("TTL") ?> <span class="optional">(<?= _("Optional") ?>)</span>
				</label>
				<input type="text" class="form-control" name="v_ttl" id="v_ttl" value="<?= htmlentities(trim($v_ttl, "'")) ?>">
			</div>

			<?php
			// Check if Cloudflare is enabled for this domain
			$cf_config = '/usr/local/hestia/data/users/'.$user.'/cloudflare/cloudflare.conf';
			$cloudflare_enabled = file_exists($cf_config);
			
			if ($cloudflare_enabled) {
				// Get zone info
				exec(HESTIA_CMD."v-list-cloudflare-zones ".$user." json", $output, $return_var);
				$zones = [];
				if ($return_var == 0 && !empty($output[0])) {
					$zones = json_decode($output[0], true);
				}
				
				$domain_on_cloudflare = false;
				foreach ($zones as $zone) {
					if ($zone['name'] === trim($v_domain, "'")) {
						$domain_on_cloudflare = true;
						break;
					}
				}
				
				if ($domain_on_cloudflare && in_array($v_type, ['A', 'AAAA', 'CNAME'])) {
					// Get current proxy status
					$record_name = $v_rec === '@' ? trim($v_domain, "'") : trim($v_rec, "'").'.'.trim($v_domain, "'");
					exec(HESTIA_CMD."v-list-dns-records ".$user." ".escapeshellarg(trim($v_domain, "'"))." json", $records_output, $records_return_var);
					$proxy_enabled = false;
					
					if ($records_return_var == 0 && !empty($records_output[0])) {
						$records = json_decode($records_output[0], true);
						foreach ($records as $record) {
							if ($record['name'] === $record_name) {
								$proxy_enabled = isset($record['PROXIED']) && $record['PROXIED'] === 'yes';
								break;
							}
						}
					}
					?>
					<div class="u-mb10">
						<label class="form-label">
							<?=_('Cloudflare Proxy')?>
							<span class="hint"><?=_('Enable Cloudflare proxy features (SSL, caching, DDoS protection)')?></span>
						</label>
						<div class="form-check">
							<input class="form-check-input" type="checkbox" name="v_proxied" id="v_proxied" value="yes" <?php if ($proxy_enabled) echo 'checked'; ?>>
							<label class="form-check-label" for="v_proxied">
								<?=_('Enable Cloudflare proxy')?>
							</label>
						</div>
					</div>
					<?php
				}
			}
			?>
		</div>

	</form>

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
	// Handle proxy checkbox state based on record type
	const typeSelect = document.getElementById('v_type');
	const proxyDiv = document.querySelector('div:has(> #v_proxied)');
	
	if (typeSelect && proxyDiv) {
		typeSelect.addEventListener('change', function() {
			const type = this.value;
			proxyDiv.style.display = ['A', 'AAAA', 'CNAME'].includes(type) ? 'block' : 'none';
		});
	}
});
</script>
