<?php
$TAB = 'CLOUDFLARE';

// Include functions
include($_SERVER['DOCUMENT_ROOT'].'/inc/main.php');

// Check token
verify_csrf($_POST);

// Get user's Cloudflare config
$cf_config = '/usr/local/hestia/data/users/'.$user.'/cloudflare/cloudflare.conf';
$cloudflare_enabled = file_exists($cf_config);

if (!empty($_POST) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle form submission
    if (isset($_POST['save'])) {
        if (!empty($_POST['v_token'])) {
            // Save new token
            $token = $_POST['v_token'];
            exec(HESTIA_CMD."v-add-cloudflare-config ".$user." ".escapeshellarg($token), $output, $return_var);
            if ($return_var == 0) {
                $_SESSION['ok_msg'] = _('Cloudflare API token has been saved.');
                $cloudflare_enabled = true;
            } else {
                $_SESSION['error_msg'] = _('Invalid API token.');
            }
        }
    }
    
    if (isset($_POST['delete'])) {
        // Remove Cloudflare configuration
        unlink($cf_config);
        $_SESSION['ok_msg'] = _('Cloudflare integration has been disabled.');
        $cloudflare_enabled = false;
    }
}

// List Cloudflare zones if enabled
$zones = [];
if ($cloudflare_enabled) {
    exec(HESTIA_CMD."v-list-cloudflare-zones ".$user." json", $output, $return_var);
    if ($return_var == 0 && !empty($output[0])) {
        $zones = json_decode($output[0], true);
    }
}

// Render page
render_page($user, $TAB, 'edit_cloudflare');
?>

<!-- Begin toolbar -->
<div class="toolbar">
    <div class="toolbar-inner">
        <div class="toolbar-buttons">
            <a class="button button-secondary button-back js-button-back" href="/list_dns.php">
                <i class="fas fa-arrow-left icon-blue"></i><?=_('Back');?>
            </a>
        </div>
        <div class="toolbar-buttons">
            <button type="submit" class="button" form="v-edit-cloudflare">
                <i class="fas fa-floppy-disk icon-purple"></i><?=_('Save');?>
            </button>
        </div>
    </div>
</div>
<!-- End toolbar -->

<div class="container">
    <form id="v-edit-cloudflare" method="POST" name="v-edit-cloudflare">
        <input type="hidden" name="token" value="<?=$_SESSION['token']?>">
        <input type="hidden" name="save" value="save">

        <!-- Begin Cloudflare section -->
        <div class="form-container">
            <h1 class="form-title"><?=_('Cloudflare Integration');?></h1>
            <?php show_alert_message(); ?>

            <div class="u-mb10">
                <label for="v_token" class="form-label"><?=_('API Token');?></label>
                <input type="password" class="form-control" name="v_token" id="v_token" 
                       value="<?=$cloudflare_enabled ? '••••••••••••••••' : ''?>" 
                       placeholder="<?=_('Enter your Cloudflare API token');?>">
                <div class="form-note">
                    <?=_('Required permissions: Zone:Read, DNS:Edit, SSL/TLS:Edit, Cache:Purge');?>
                </div>
            </div>

            <?php if ($cloudflare_enabled): ?>
                <div class="u-mb20">
                    <h2 class="form-subtitle"><?=_('Connected Domains');?></h2>
                    <div class="u-mb10">
                        <?php if (!empty($zones)): ?>
                            <table class="data-col1">
                                <tr>
                                    <th><?=_('Domain');?></th>
                                    <th><?=_('Status');?></th>
                                    <th><?=_('SSL Mode');?></th>
                                    <th><?=_('Dev Mode');?></th>
                                    <th><?=_('Actions');?></th>
                                </tr>
                                <?php foreach ($zones as $zone): ?>
                                    <?php
                                    // Get SSL config
                                    exec(HESTIA_CMD."v-change-cloudflare-ssl ".$user." ".escapeshellarg($zone['name'])." get", $ssl_output, $ssl_return_var);
                                    $ssl_mode = 'unknown';
                                    if ($ssl_return_var == 0 && !empty($ssl_output[0])) {
                                        $ssl_data = json_decode($ssl_output[0], true);
                                        $ssl_mode = $ssl_data['ssl_mode'] ?? 'unknown';
                                    }
                                    ?>
                                    <tr>
                                        <td><?=htmlspecialchars($zone['name'])?></td>
                                        <td>
                                            <span class="status-icon status-icon-<?=$zone['status']==='active'?'active':'pending'?>">
                                                <?=ucfirst($zone['status'])?>
                                            </span>
                                        </td>
                                        <td>
                                            <select class="form-select js-ssl-mode" data-domain="<?=htmlspecialchars($zone['name'])?>">
                                                <option value="off" <?=$ssl_mode==='off'?'selected':''?>><?=_('Off')?></option>
                                                <option value="flexible" <?=$ssl_mode==='flexible'?'selected':''?>><?=_('Flexible')?></option>
                                                <option value="full" <?=$ssl_mode==='full'?'selected':''?>><?=_('Full')?></option>
                                                <option value="strict" <?=$ssl_mode==='strict'?'selected':''?>><?=_('Strict')?></option>
                                            </select>
                                        </td>
                                        <td>
                                            <button class="button button-secondary js-dev-mode" data-domain="<?=htmlspecialchars($zone['name'])?>">
                                                <i class="fas fa-code"></i> <?=_('Toggle Dev Mode')?>
                                            </button>
                                        </td>
                                        <td>
                                            <button class="button button-secondary js-purge-cache" data-domain="<?=htmlspecialchars($zone['name'])?>">
                                                <i class="fas fa-sync"></i> <?=_('Purge Cache')?>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </table>
                        <?php else: ?>
                            <p class="form-note"><?=_('No domains connected to Cloudflare');?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="u-mb20">
                    <button type="submit" class="button button-danger" name="delete" value="delete">
                        <i class="fas fa-trash icon-white"></i><?=_('Disable Cloudflare Integration');?>
                    </button>
                </div>

                <script>
                document.addEventListener('DOMContentLoaded', function() {
                    // Handle SSL mode changes
                    document.querySelectorAll('.js-ssl-mode').forEach(function(select) {
                        select.addEventListener('change', function() {
                            const domain = this.dataset.domain;
                            const mode = this.value;
                            fetch('/edit/cloudflare/?domain=' + encodeURIComponent(domain) + '&ssl=' + mode + '&token=<?=$_SESSION["token"]?>')
                                .then(response => response.json())
                                .then(data => {
                                    if (data.error) {
                                        alert(data.error);
                                    }
                                });
                        });
                    });

                    // Handle development mode toggle
                    document.querySelectorAll('.js-dev-mode').forEach(function(button) {
                        button.addEventListener('click', function(e) {
                            e.preventDefault();
                            const domain = this.dataset.domain;
                            fetch('/edit/cloudflare/?domain=' + encodeURIComponent(domain) + '&devmode=toggle&token=<?=$_SESSION["token"]?>')
                                .then(response => response.json())
                                .then(data => {
                                    if (data.error) {
                                        alert(data.error);
                                    } else {
                                        alert(data.message || 'Development mode toggled');
                                    }
                                });
                        });
                    });

                    // Handle cache purge
                    document.querySelectorAll('.js-purge-cache').forEach(function(button) {
                        button.addEventListener('click', function(e) {
                            e.preventDefault();
                            if (!confirm('<?=_('Are you sure you want to purge the cache?')?>')) {
                                return;
                            }
                            const domain = this.dataset.domain;
                            fetch('/edit/cloudflare/?domain=' + encodeURIComponent(domain) + '&purge=all&token=<?=$_SESSION["token"]?>')
                                .then(response => response.json())
                                .then(data => {
                                    if (data.error) {
                                        alert(data.error);
                                    } else {
                                        alert('Cache purged successfully');
                                    }
                                });
                        });
                    });
                });
                </script>
            <?php endif; ?>
        </div>
        <!-- End Cloudflare section -->
    </form>
</div>