<?php

namespace App\Utility;

use App\Mail\MailManager;
use App\Models\EmailTemplate;
use App\Models\User;
use Mail;

class EmailUtility
{
    // Customer registration email to Admin & Customer
    public static function customer_registration_email($emailIdentifier, $user, $password = null){
        $admin = get_admin();
        $emailSendTo = $emailIdentifier == 'customer_reg_email_to_admin' ? $admin->email : $user->email;
        $emailTemplate = EmailTemplate::whereIdentifier($emailIdentifier)->first();

        $emailSubject = $emailTemplate->subject;
        $emailSubject = str_replace('[[customer_name]]', $user->name, $emailSubject);
        $emailSubject = str_replace('[[store_name]]', get_setting('site_name'), $emailSubject);

        $emailBody = $emailTemplate->default_text;
        $email_or_phone = $user->email != null ? $user->email : $user->phone;
        $emailBody = str_replace('[[store_name]]', get_setting('site_name'), $emailBody);
        $emailBody = str_replace('[[admin_name]]', $admin->name, $emailBody);
        $emailBody = str_replace('[[customer_name]]', $user->name, $emailBody);
        $emailBody = str_replace('[[email]]', $user->email, $emailBody);
        $emailBody = str_replace('[[password]]', $password, $emailBody);
        $emailBody = str_replace('[[email/phone]]', $email_or_phone, $emailBody);
        $emailBody = str_replace('[[date]]', date('d-m-Y', strtotime($user->created_at)), $emailBody);
        $emailBody = str_replace('[[admin_email]]', $admin->email, $emailBody);
        
        $array['subject'] = $emailSubject;
        $array['content'] = $emailBody;

        Mail::to($emailSendTo)->queue(new MailManager($array));
    }

    
    // User Email Verification
    public static function email_verification($user, $userType){
        $emailIdentifier =  'email_verification_'.$userType;
        $verification_code = encrypt($user->id);

        // User Veridication code add
        $user->verification_code = $verification_code;
        $user->save();

        $emailTemplate = EmailTemplate::whereIdentifier($emailIdentifier)->first();

        $emailSubject = $emailTemplate->subject;
        $emailSubject = str_replace('[[store_name]]', get_setting('site_name'), $emailSubject);
        
        $emailBody = $emailTemplate->default_text;
        $link = route('email.verification.confirmation', $verification_code);
        $verifyButton = '<div style="display: flex; justify-content: center; padding-bottom:4px;">
            <a href="'.$link.'" target="_blank" style="background: #0b60bd; text-decoration:none; padding: 1.4rem 2rem; color:#fff;border-radius: .3rem;">Click here</a>
        </div>';
        
        $emailBody = str_replace('[[store_name]]', get_setting('site_name'), $emailBody);
        $emailBody = str_replace('[[customer_name]]', $user->name, $emailBody);
        $emailBody = str_replace('[[seller_name]]', $user->name, $emailBody);
        $emailBody = str_replace('[[verify_email_button]]', $verifyButton, $emailBody);
        $emailBody = str_replace('[[admin_email]]', get_admin()->email, $emailBody);

        $array['subject'] = $emailSubject;
        $array['content'] = $emailBody;

        Mail::to($user->email)->queue(new MailManager($array));

    }

    // Seller Payout emails
    public static function seller_payout($emailIdentifiers, $seller, $amount, $payment_method = null){
        $admin = get_admin();
        $shop = $seller->shop;
        foreach($emailIdentifiers as $emailIdentifier){
            $emailTemplate = EmailTemplate::whereIdentifier($emailIdentifier)->first();
            if($emailTemplate != null && $emailTemplate->status == 1){
                $emailSendTo = $emailTemplate->receiver == 'admin' ? $admin->email : $seller->email;

                $emailSubject = $emailTemplate->subject;
                $emailSubject = str_replace('[[shop_name]]', $shop->name, $emailSubject);
                $emailSubject = str_replace('[[store_name]]', get_setting('site_name'), $emailSubject);

                $emailBody = $emailTemplate->default_text;
                $emailBody = str_replace('[[admin_name]]', $admin->name, $emailBody);
                $emailBody = str_replace('[[shop_name]]', $shop->name, $emailBody);
                $emailBody = str_replace('[[shop_email]]', $seller->email, $emailBody);
                $emailBody = str_replace('[[amount]]', single_price($amount), $emailBody);
                $emailBody = str_replace('[[payment_method]]', $payment_method, $emailBody);
                $emailBody = str_replace('[[store_name]]', get_setting('site_name'), $emailBody);;
                $emailBody = str_replace('[[date]]', date('d-m-Y'), $emailBody);
                $emailBody = str_replace('[[admin_email]]', get_admin()->email, $emailBody);

                $array['subject'] = $emailSubject;
                $array['content'] = $emailBody;

                try {
                    Mail::to($emailSendTo)->queue(new MailManager($array));
                } catch (\Exception $e) {}
            }
        
        }
    }

    
}
