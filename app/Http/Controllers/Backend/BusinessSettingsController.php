<?php

namespace App\Http\Controllers\Backend;

use App\Models\BusinessSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class BusinessSettingsController
{

    /**
     * Update The Business Settings
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request)
    {

        foreach ($request->types as $key => $type) {
            if($type == 'site_name'){
                $this->overWriteEnvFile('APP_NAME', $request[$type]);
            }
            if($type == 'timezone'){
                $this->overWriteEnvFile('APP_TIMEZONE', $request[$type]);
            }
            else {
                $lang = null;
                if(gettype($type) == 'array'){
                    $lang = array_key_first($type);
                    $type = $type[$lang];
                    $business_settings = BusinessSetting::where('type', $type)->where('lang',$lang)->first();
                }else{
                    $business_settings = BusinessSetting::where('type', $type)->first();
                }

                if($business_settings!=null){
                    if(gettype($request[$type]) == 'array'){
                        $business_settings->value = json_encode($request[$type]);
                    }
                    else {
                        $business_settings->value = $request[$type];
                    }
                    $business_settings->lang = $lang;
                    $business_settings->save();
                }
                else{
                    $business_settings = new BusinessSetting;
                    $business_settings->type = $type;
                    if(gettype($request[$type]) == 'array'){
                        $business_settings->value = json_encode($request[$type]);
                    }
                    else {
                        $business_settings->value = $request[$type];
                    }
                    $business_settings->lang = $lang;
                    $business_settings->save();
                }
            }
        }

        Artisan::call('cache:clear');

        flash("Settings updated successfully")->success();
        return back();
    }

    /**
     * Keys that Super Admin may write via the SMTP screen. Anything else is ignored.
     *
     * @var list<string>
     */
    private const ENV_KEY_ALLOWLIST = [
        'MAIL_DRIVER',
        'MAIL_MAILER',
        'MAIL_HOST',
        'MAIL_PORT',
        'MAIL_USERNAME',
        'MAIL_PASSWORD',
        'MAIL_ENCRYPTION',
        'MAIL_FROM_ADDRESS',
        'MAIL_FROM_NAME',
        'MAILGUN_DOMAIN',
        'MAILGUN_SECRET',
        'APP_NAME',
        'APP_TIMEZONE',
    ];

    /**
     * overWrite the Env File values.
     * Launch Step 4: Super Admin only; never accept arbitrary keys from HTTP.
     *
     * @param  string $type
     * @param  string $val
     * @return bool
     */
    public function overWriteEnvFile($type, $val)
    {
        if (! auth()->user()?->isSuperAdmin()) {
            return false;
        }

        if (! in_array($type, self::ENV_KEY_ALLOWLIST, true)) {
            return false;
        }

        if(env('DEMO_MODE') != 'On'){
            $path = base_path('.env');
            if (file_exists($path)) {
                $val = '"'.trim($val).'"';
                if(is_numeric(strpos(file_get_contents($path), $type)) && strpos(file_get_contents($path), $type) >= 0){
                    file_put_contents($path, str_replace(
                        $type.'="'.env($type).'"', $type.'='.$val, file_get_contents($path)
                    ));
                }
                else{
                    file_put_contents($path, file_get_contents($path)."\r\n".$type.'='.$val);
                }
            }
        }
        return true;
    }

    /**
     * Update the API key's for other methods.
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function env_key_update(Request $request)
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);

        foreach ($request->types as $key => $type) {
            if (! in_array($type, self::ENV_KEY_ALLOWLIST, true)) {
                continue;
            }

            // Never wipe secrets when the form field is left blank.
            if (in_array($type, ['MAIL_PASSWORD', 'MAILGUN_SECRET'], true) && blank($request->input($type))) {
                continue;
            }

            $this->overWriteEnvFile($type, $request[$type]);

            // Laravel 11 reads MAIL_MAILER; the SMTP screen still posts MAIL_DRIVER.
            if ($type === 'MAIL_DRIVER') {
                $this->overWriteEnvFile('MAIL_MAILER', $request[$type]);
            }
        }

        flash("Settings updated successfully")->success();
        return back();
    }

    public function smtp_settings(Request $request)
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);

        return view('backend.setup_configurations.smtp_settings');
    }

}
