<?php namespace SendGrid\Mail;
/**
  * This helper builds the request body for a /mail/send API call.
  *
  * PHP version 5.6, 7
  *
  * @author    Elmer Thomas <dx@sendgrid.com>
  * @copyright 2017 SendGrid
  * @license   https://opensource.org/licenses/MIT The MIT License
  * @version   GIT: <git_id>
  * @link      http://packagist.org/packages/sendgrid/sendgrid
  */
/**
  * The final request body object
  */
class Mail implements \JsonSerializable
{
    const VERSION = '1.0.0';

    protected $namespace = 'SendGrid';

    private $from;
    private $subject;
    private $contents;
    private $attachments;
    private $template_id;
    private $sections;
    private $headers;
    private $categories;
    private $custom_args;
    private $send_at;
    private $batch_id;
    private $asm;
    private $ip_pool_name;
    private $mail_settings;
    private $tracking_settings;
    private $reply_to;

    public $personalization = null;

    public function __construct(
        $from = null,
        $to = null,
        $subject = null,
        $plainTextContent = null,
        $htmlContent = null,
        array $globalSubstitutions = null
    ) {
        if (!$from
            && !$to
            && !$subject
            && !$plainTextContent
            && !$htmlContent
            && !$globalSubstitutions
        ) {
            return;
        }
        $this->setFrom($from);
        if (!is_array($subject)) {
            $this->setSubject($subject);
            $subjectCount = null;
        } else {
            $subjectCount = 1;
        }
        if (!is_array($to)) {
            $to = [ $to ];
        }
        foreach ($to as $email) {
            $personalization = new Personalization();
            $personalization->addTo($email);
            if ($subs = $email->getSubstitions()) {
                foreach ($subs as $key => $value) {
                    $personalization->addSubstitution($key, $value);
                }
            }
            if (is_array($subject)) {
                $personalization->setSubject($subject[$subjectCount - 1]);
                $subjectCount++;
            } else {
                if ($subject = $email->getSubject()) {
                    $personalization->setSubject($subject);
                }
            }
            if (is_array($globalSubstitutions)) {
                foreach ($globalSubstitutions as $key => $value) {
                    $personalization->addSubstitution($key, $value);
                }
            }
            $this->addPersonalization($personalization);
        }
        $this->addContent($plainTextContent);
        $this->addContent($htmlContent);
    }

    public function setFrom($email, $name=null)
    {
        if ($name != null ) {
            $this->from = new From($email, $name);
        } else {
            $this->from = $email;
        }
    }

    public function getFrom()
    {
        return $this->from;
    }

    public function addRecipientEmail(
        $emailType,
        $email,
        $name = null,
        $personalizationIndex = null,
        $personalization = null
    ) {
        $personalizationFunctionCall = "add".$emailType;
        $emailType = "\SendGrid\Mail\\".$emailType;
        if ($name != null) {
            $email = new $emailType($email, $name);
        }
        if ($personalization != null) {
            $this->addPersonalization($personalization);
            return;
        } else {
            if ($this->personalization[0] != null) {
                $this->personalization[0]->$personalizationFunctionCall($email);
            } else {
                $personalization = new Personalization();
                $personalization->$personalizationFunctionCall($email);
                if (($personalizationIndex != 0)
                    && ($this->getPersonalizationCount() <= personalizationIndex)
                ) {
                    $this->personalization[personalizationIndex] = $personalization;
                } else {
                    $this->addPersonalization($personalization);
                }
            }
            return;
        }
    }

    public function addTo(
        $to,
        $name = null,
        $personalizationIndex = null,
        $personalization = null
    ) {
        $this->addRecipientEmail(
            "To",
            $to,
            $name,
            $personalizationIndex,
            $personalization
        );
    }

    public function addTos(
        $toEmails,
        $personalizationIndex = null,
        $personalization = null) {
        foreach ($toEmails as $email) {
            $this->addTo(
                $email,
                null,
                $personalizationIndex,
                $personalization
            );
        }
    }

    public function addCc(
        $cc,
        $name = null,
        $personalizationIndex = null,
        $personalization = null
    ) {
        $this->addRecipientEmail(
            "Cc",
            $cc,
            $name,
            $personalizationIndex,
            $personalization
        );
    }

    public function addCcs(
        $ccEmails,
        $personalizationIndex = null,
        $personalization = null) {
        foreach ($ccEmails as $email) {
            $this->addCc(
                $email,
                null,
                $personalizationIndex,
                $personalization
            );
        }
    }

    public function addBcc(
        $bcc,
        $name = null,
        $personalizationIndex = null,
        $personalization = null
    ) {
        $this->addRecipientEmail(
            "Bcc",
            $bcc,
            $name,
            $personalizationIndex,
            $personalization
        );
    }

    public function addBccs(
        $bccEmails,
        $personalizationIndex = null,
        $personalization = null) {
        foreach ($bccEmails as $email) {
            $this->addBcc(
                $email,
                null,
                $personalizationIndex,
                $personalization
            );
        }
    }

    public function addPersonalization($personalization)
    {
        $this->personalization[] = $personalization;
    }

    public function getPersonalizations()
    {
        return $this->personalization;
    }

    public function setSubject($subject)
    {
        if ($subject instanceof Subject) {
            $this->subject = $subject;
        } else {
            $this->subject = new Subject($subject);
        }
    }

    public function getSubject()
    {
        return $this->subject;
    }

    public function addContent($content, $value = null)
    {
        if ($value != null) {
            $content = new Content($content, $value);
        }
        $this->contents[] = $content;
    }

    public function getContents()
    {
        // TODO: Ensure text/plain is always first
        return $this->contents;
    }

    public function addAttachment($attachment)
    {
        $this->attachments[] = $attachment;
    }

    public function getAttachments()
    {
        return $this->attachments;
    }

    public function setTemplateId($template_id)
    {
        $this->template_id = $template_id;
    }

    public function getTemplateId()
    {
        return $this->template_id;
    }

    public function addSection($key, $value)
    {
        $this->sections[$key] = $value;
    }

    public function getSections()
    {
        return $this->sections;
    }

    public function addHeader($key, $value)
    {
        $this->headers[$key] = $value;
    }

    public function getHeaders()
    {
        return $this->headers;
    }

    public function addCategory($category)
    {
        $this->categories[] = $category;
    }

    public function getCategories()
    {
        return $this->categories;
    }

    public function addCustomArg($key, $value)
    {
        $this->custom_args[$key] = (string)$value;
    }

    public function getCustomArgs()
    {
        return $this->custom_args;
    }

    public function setSendAt($send_at)
    {
        $this->send_at = $send_at;
    }

    public function getSendAt()
    {
        return $this->send_at;
    }

    public function setBatchId($batch_id)
    {
        $this->batch_id = $batch_id;
    }

    public function getBatchId()
    {
        return $this->batch_id;
    }

    public function setASM($asm)
    {
        $this->asm = $asm;
    }

    public function getASM()
    {
        return $this->asm;
    }

    public function setIpPoolName($ip_pool_name)
    {
        $this->ip_pool_name = $ip_pool_name;
    }

    public function getIpPoolName()
    {
        return $this->ip_pool_name;
    }

    public function setMailSettings($mail_settings)
    {
        $this->mail_settings = $mail_settings;
    }

    public function getMailSettings()
    {
        return $this->mail_settings;
    }

    public function setTrackingSettings($tracking_settings)
    {
        $this->tracking_settings = $tracking_settings;
    }

    public function getTrackingSettings()
    {
        return $this->tracking_settings;
    }

    public function setReplyTo($reply_to)
    {
        $this->reply_to = $reply_to;
    }

    public function getReplyTo()
    {
        return $this->reply_to;
    }

    public function jsonSerialize()
    {
        return array_filter(
            [
                'from'              => $this->getFrom(),
                'personalizations'  => $this->getPersonalizations(),
                'subject'           => $this->getSubject(),
                'content'           => $this->getContents(),
                'attachments'       => $this->getAttachments(),
                'template_id'       => $this->getTemplateId(),
                'sections'          => $this->getSections(),
                'headers'           => $this->getHeaders(),
                'categories'        => $this->getCategories(),
                'custom_args'       => $this->getCustomArgs(),
                'send_at'           => $this->getSendAt(),
                'batch_id'          => $this->getBatchId(),
                'asm'               => $this->getASM(),
                'ip_pool_name'      => $this->getIpPoolName(),
                'mail_settings'     => $this->getMailSettings(),
                'tracking_settings' => $this->getTrackingSettings(),
                'reply_to'          => $this->getReplyTo()
            ],
            function ($value) {
                return $value !== null;
            }
        ) ?: null;
    }
}