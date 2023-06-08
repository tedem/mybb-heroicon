<?php

/**
 * Heroicon
 *
 * It allows you to use heroicons created by the makers of Tailwind CSS in mybb posts as MyCode.
 *
 * @author Medet "tedem" Erdal <hello@tedem.dev>
 */

// mybb
if (! \defined('IN_MYBB')) {
    die('(-_*) This file cannot be accessed directly.');
}

// version controls
if (version_compare(phpversion(), '7.0', '<=') || version_compare(phpversion(), '8.3', '>=')) {
    die('(T_T) PHP version is not compatible for this plugin!');
}

// constants
\define('TEDEM_HEROICON_ID', 'heroicon');
\define('TEDEM_HEROICON_NAME', ucfirst(TEDEM_HEROICON_ID));
\define('TEDEM_HEROICON_AUTHOR', 'tedem');
\define('TEDEM_HEROICON_VERSION', '1.0.0');

// You can arrange it according to your own class format.
\define('TEDEM_HEROICON_CLASS', 't-code-heroicon');

// hooks
if (! \defined('IN_ADMINCP')) {
    $plugins->add_hook('parse_message', 'heroicon_main');
}

function heroicon_info(): array
{
    $description = <<<'HTML'
<div style="margin-top: 1em;">
    It allows you to use heroicons created by the makers of Tailwind CSS in mybb posts as MyCode.
</div>
HTML;

    if (heroicon_donation_status()) {
        $description = $description . heroicon_donation();
    }

    return [
        'name'          => TEDEM_HEROICON_NAME,
        'description'   => $description,
        'website'       => 'https://tedem.dev',
        'author'        => TEDEM_HEROICON_AUTHOR,
        'authorsite'    => 'https://tedem.dev',
        'version'       => TEDEM_HEROICON_VERSION,
        'codename'      => TEDEM_HEROICON_AUTHOR . '_' . TEDEM_HEROICON_ID,
        'compatibility' => '18*',
    ];
}

function heroicon_install(): void
{
    global $cache;

    // add cache
    $plugins = $cache->read(TEDEM_HEROICON_AUTHOR);

    $plugins[TEDEM_HEROICON_ID] = [
        'name'     => TEDEM_HEROICON_NAME,
        'author'   => TEDEM_HEROICON_AUTHOR,
        'version'  => TEDEM_HEROICON_VERSION,
        'donation' => 1,
    ];

    $cache->update(TEDEM_HEROICON_AUTHOR, $plugins);
}

function heroicon_is_installed(): bool
{
    global $cache;

    // has cache
    $plugins = $cache->read(TEDEM_HEROICON_AUTHOR);

    return isset($plugins[TEDEM_HEROICON_ID]);
}

function heroicon_uninstall(): void
{
    global $db, $cache;

    // remove cache
    $plugins = $cache->read(TEDEM_HEROICON_AUTHOR);

    unset($plugins[TEDEM_HEROICON_ID]);

    $cache->update(TEDEM_HEROICON_AUTHOR, $plugins);

    if (\count($plugins) == 0) {
        $db->delete_query('datacache', "title='" . TEDEM_HEROICON_AUTHOR . "'");
    }
}

function heroicon_activate(): void
{
    require_once MYBB_ROOT . '/inc/adminfunctions_templates.php';

    $styles = heroicon_styles();

    // If it was added before, remove it.
    find_replace_templatesets(
        'headerinclude',
        '#' . preg_quote("\r\r" . $styles) . '#',
        ''
    );

    // Add styles.
    find_replace_templatesets(
        'headerinclude',
        '#' . preg_quote('{$stylesheets}') . '#',
        "{\$stylesheets}\r\r{$styles}",
    );
}

function heroicon_deactivate(): void
{
    require_once MYBB_ROOT . '/inc/adminfunctions_templates.php';

    $styles = heroicon_styles();

    // Remove styles.
    find_replace_templatesets(
        'headerinclude',
        '#' . preg_quote("\r\r" . $styles) . '#',
        ''
    );
}

function heroicon_main($message): string
{
    $message = preg_replace_callback(
        '/(?<!\[code\])(?<!\[php\])\[heroicon=\'(.*?)\'\](?!\[\/code\]|\[\/php\])/i',
        function ($matches) {
            $file = MYBB_ROOT . 'images/heroicons/' . $matches[1] . '.svg';

            // Check if the SVG file exists.
            if (! file_exists($file)) {
                return '';
            }

            // Read the contents of the SVG file.
            $svg = file_get_contents($file);

            // Remove line breaks and tabs from the SVG code.
            $svg = str_replace(["\r", "\n", "\r\n", "\t"], '', $svg);

            // Return the HTML code for the icon.
            return '<span class="' . TEDEM_HEROICON_CLASS . '">' . $svg . '</span>';
        },
        $message
    );

    return $message;
}

function heroicon_styles(): string
{
    return '<style>.t-code-heroicon{display:inline-block;margin-top:-2px;vertical-align:middle}.t-code-heroicon svg{height:1.25rem;width:1.25rem}.t-code-heroicon svg path{stroke:currentColor}</style>';
}

function heroicon_donation(): string
{
    global $mybb;

    heroicon_donation_edit();

    $BMC  = '<a href="https://www.buymeacoffee.com/tedem"><b>Buy me a coffee</b></a>';
    $KOFI = '<a href="https://ko-fi.com/tedem"><b>KO-FI</b></a>';

    $close_link   = 'index.php?module=config-plugins&' . TEDEM_HEROICON_ID . '=deactivate-donation&my_post_key=' . $mybb->post_code;
    $close_button = ' &mdash; <a href="' . $close_link . '"><b>Close Donation</b></a>';

    $message = '<b>Donation:</b> Support for new plugins, themes, etc. via ' . $BMC . ' or ' . $KOFI . $close_button;

    return '<div style="margin-top: 1em;">' . $message . '</div>';
}

function heroicon_donation_status(): bool
{
    global $cache;

    $donation = $cache->read(TEDEM_HEROICON_AUTHOR);

    return isset($donation[TEDEM_HEROICON_ID]['donation'])
        && $donation[TEDEM_HEROICON_ID]['donation'] == 1;
}

function heroicon_donation_edit(): void
{
    global $mybb;

    if ($mybb->get_input('my_post_key') == $mybb->post_code) {
        global $cache;

        $plugins = $cache->read(TEDEM_HEROICON_AUTHOR);

        if ($mybb->get_input(TEDEM_HEROICON_ID) == 'deactivate-donation') {
            $plugins[TEDEM_HEROICON_ID]['donation'] = 0;

            $cache->update(TEDEM_HEROICON_AUTHOR, $plugins);

            flash_message('The donation message has been successfully closed.', 'success');
            admin_redirect('index.php?module=config-plugins');
        }
    }
}
