<?php

declare(strict_types=1);

/**
 * Heroicon
 *
 * This plugin enables the use of Heroicons,
 * designed by the creators of Tailwind CSS,
 * directly within MyBB posts through MyCode integration.
 *
 * @author Medet "tedem" Erdal <hello@tedem.dev>
 *
 * @version 1.0.0
 */

// Disallow direct access to this file for security reasons
if (! \defined('IN_MYBB')) {
    exit('(-_*) This file cannot be accessed directly.');
}

// Check PHP version
if (version_compare(phpversion(), '7.0', '<=') || version_compare(phpversion(), '8.3', '>=')) {
    exit('(T_T) PHP version is not compatible for this plugin!');
}

// Constants
\define('TEDEM_HEROICON_ID', 'heroicon');
\define('TEDEM_HEROICON_NAME', ucfirst(TEDEM_HEROICON_ID));
\define('TEDEM_HEROICON_AUTHOR', 'tedem');
\define('TEDEM_HEROICON_VERSION', '1.0.0');
\define('TEDEM_HEROICON_CLASS', 't-code-heroicon');

// Hooks
if (! \defined('IN_ADMINCP')) {
    $plugins->add_hook('parse_message', 'heroicon_main');
}

/**
 * Returns the plugin information.
 *
 * @return array The plugin information.
 */
function heroicon_info(): array
{
    $description = <<<'HTML'
<div style="margin-top: 1em;">
    This plugin enables the use of Heroicons, designed by the creators of Tailwind CSS, directly within MyBB posts through MyCode integration.
</div>
HTML;

    if (heroicon_donation_status()) {
        $description .= heroicon_donation();
    }

    return [
        'name' => TEDEM_HEROICON_NAME,
        'description' => $description,
        'website' => 'https://tedem.dev',
        'author' => TEDEM_HEROICON_AUTHOR,
        'authorsite' => 'https://tedem.dev',
        'version' => TEDEM_HEROICON_VERSION,
        'codename' => TEDEM_HEROICON_AUTHOR.'_'.TEDEM_HEROICON_ID,
        'compatibility' => '18*',
    ];
}

/**
 * Installs the plugin.
 */
function heroicon_install(): void
{
    global $cache;

    $plugins = $cache->read(TEDEM_HEROICON_AUTHOR);

    $plugins[TEDEM_HEROICON_ID] = [
        'name' => TEDEM_HEROICON_NAME,
        'author' => TEDEM_HEROICON_AUTHOR,
        'version' => TEDEM_HEROICON_VERSION,
        'donation' => 1,
    ];

    $cache->update(TEDEM_HEROICON_AUTHOR, $plugins);
}

/**
 * Checks if the plugin is installed.
 */
function heroicon_is_installed(): bool
{
    global $cache;

    $plugins = $cache->read(TEDEM_HEROICON_AUTHOR);

    return isset($plugins[TEDEM_HEROICON_ID]);
}

/**
 * Uninstalls the plugin.
 */
function heroicon_uninstall(): void
{
    global $db, $cache;

    $plugins = $cache->read(TEDEM_HEROICON_AUTHOR);

    unset($plugins[TEDEM_HEROICON_ID]);

    $cache->update(TEDEM_HEROICON_AUTHOR, $plugins);

    if (\count($plugins) === 0) {
        $db->delete_query('datacache', "title='".TEDEM_HEROICON_AUTHOR."'");
    }
}

/**
 * Activates the plugin.
 */
function heroicon_activate(): void
{
    require_once MYBB_ROOT.'/inc/adminfunctions_templates.php';

    $styles = heroicon_styles();

    find_replace_templatesets(
        'headerinclude',
        '#'.preg_quote("\r\r".$styles).'#',
        ''
    );

    find_replace_templatesets(
        'headerinclude',
        '#'.preg_quote('{$stylesheets}').'#',
        "{\$stylesheets}\r\r{$styles}",
    );
}

/**
 * Deactivates the plugin.
 */
function heroicon_deactivate(): void
{
    require_once MYBB_ROOT.'/inc/adminfunctions_templates.php';

    $styles = heroicon_styles();

    find_replace_templatesets(
        'headerinclude',
        '#'.preg_quote("\r\r".$styles).'#',
        ''
    );
}

/**
 * Replaces [heroicon='icon_name'] BBCode with the corresponding SVG icon.
 *
 * This function searches for the [heroicon='icon_name'] BBCode in the given message
 * and replaces it with the corresponding SVG icon wrapped in a span element.
 * The SVG icons are located in the 'images/heroicons' directory.
 *
 * @param  string  $message  The message containing the BBCode to be replaced.
 * @return string The message with the BBCode replaced by the corresponding SVG icon.
 */
function heroicon_main($message): string
{
    return preg_replace_callback(
        '/(?<!\[code\])(?<!\[php\])\[heroicon=\'(.*?)\'\](?!\[\/code\]|\[\/php\])/i',
        function ($matches): string {
            $file = MYBB_ROOT.'images/heroicons/'.$matches[1].'.svg';

            // Check if the SVG file exists.
            if (! file_exists($file)) {
                return '';
            }

            // Read the contents of the SVG file.
            $svg = file_get_contents($file);

            // Remove line breaks and tabs from the SVG code.
            $svg = str_replace(["\r", "\n", "\r\n", "\t"], '', $svg);

            // Return the HTML code for the icon.
            return '<span class="'.TEDEM_HEROICON_CLASS.'">'.$svg.'</span>';
        },
        $message
    );
}

/**
 * Generates and returns the CSS styles for the heroicon plugin.
 *
 * @return string The CSS styles for the heroicon plugin.
 */
function heroicon_styles(): string
{
    return '<style>.t-code-heroicon{display:inline-block;margin-top:-2px;vertical-align:middle}.t-code-heroicon svg{height:1.25rem;width:1.25rem}.t-code-heroicon svg path{stroke:currentColor}</style>';
}

/**
 * Generates a donation message with links to support the developer.
 *
 * This function creates a donation message that includes links to "Buy me a coffee" and "KO-FI"
 * for supporting the developer. It also includes a link to close the donation message.
 *
 * @global object $mybb The MyBB core object.
 *
 * @return string The HTML string containing the donation message.
 */
function heroicon_donation(): string
{
    global $mybb;

    heroicon_donation_edit();

    $BMC = '<a href="https://www.buymeacoffee.com/tedem"><b>Buy me a coffee</b></a>';
    $KOFI = '<a href="https://ko-fi.com/tedem"><b>KO-FI</b></a>';

    $close_link = 'index.php?module=config-plugins&'.TEDEM_HEROICON_AUTHOR.'-'.TEDEM_HEROICON_ID.'=deactivate-donation&my_post_key='.$mybb->post_code;
    $close_button = ' &mdash; <a href="'.$close_link.'"><b>Close Donation</b></a>';

    $message = '<b>Donation:</b> Support for new plugins, themes, etc. via '.$BMC.' or '.$KOFI.$close_button;

    return '<div style="margin-top: 1em;">'.$message.'</div>';
}

/**
 * Checks the donation status for the current user.
 *
 * This function reads the donation status from the cache and determines
 * if the user has made a donation.
 *
 * @global array $cache The global cache array.
 *
 * @return bool True if the user has made a donation, false otherwise.
 */
function heroicon_donation_status(): bool
{
    global $cache;

    $donation = $cache->read(TEDEM_HEROICON_AUTHOR);

    return isset($donation[TEDEM_HEROICON_ID]['donation'])
        && $donation[TEDEM_HEROICON_ID]['donation'] === 1;
}

/**
 * Handles the donation edit action.
 *
 * This function checks if the provided post key matches the expected post code.
 * If the post key is valid and the donation action is set to 'deactivate-donation',
 * it updates the plugin's donation status to inactive and updates the cache.
 * A success message is then flashed and the user is redirected to the plugins configuration page.
 *
 * @global array $mybb The MyBB core object containing request data.
 * @global object $cache The MyBB cache object used to read and update cache data.
 */
function heroicon_donation_edit(): void
{
    global $mybb;

    if ($mybb->get_input('my_post_key') === $mybb->post_code) {
        global $cache;

        $plugins = $cache->read(TEDEM_HEROICON_AUTHOR);

        if ($mybb->get_input(TEDEM_HEROICON_AUTHOR.'-'.TEDEM_HEROICON_ID) === 'deactivate-donation') {
            $plugins[TEDEM_HEROICON_ID]['donation'] = 0;

            $cache->update(TEDEM_HEROICON_AUTHOR, $plugins);

            flash_message('The donation message has been successfully closed.', 'success');
            admin_redirect('index.php?module=config-plugins');
        }
    }
}
