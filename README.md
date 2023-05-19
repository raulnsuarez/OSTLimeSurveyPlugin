# [osTicket](https://github.com/osTicket/) - LimeSurvey Plugin

Automatically enroll ticket users in a LimeSurvey survey.

## Caveats/Assumptions:

- Assumes [osTicket](https://github.com/osTicket/) v1.17+ is installed.
- Assumes PHP 7.4+ is being used on the server. (Earlier versions will cause crash when plugin enabled)

## Install the plugin
- Download master [zip](https://github.com/raulnsuarez/OSTLimeSurveyPlugin/archive/refs/heads/main.zip) and extract into `/include/plugins/autocloser`
- Install by selecting `Add New Plugin` from the Admin Panel => Manage => Plugins page, then select `Install` next to `Automatic Surveys for Tickets`.

## Configure the plugin
Before saving the server settings the Plugin will check if the configuration is valid, so be sure the LimeSurvey Remote API is enabled and successfully configured. A guide to set up this is at https://manual.limesurvey.org/RemoteControl_2_API.

_This is an example of a successful configuration using a Test Survey Server:_

<img width="377" alt="image" src="https://github.com/raulnsuarez/OSTLimeSurveyPlugin/assets/29768685/3bf0c9b8-0f11-4f0a-83f5-ee6b60e7a2d7">

**_Note:_** To use the Custom Event _`"ticket.closed"`_, it needs to be defined in the `class.ticket.php` file, inside the Ticket class, in the `setStatus` method, before the `break` statement at the end of the "Close Ticket" logic. This is because this event is not defined in the Original OSTicket code. At the moment, this is not included in the Plugin. The change in the setStatus function will be available like the next:
```php #23
#.... Previous code ....
switch ($status->getState()) {
    case 'closed':
        // Check if ticket is closeable
        $closeable = $force_close ? true : $this->isCloseable();
        if ($closeable !== true)
            $errors['err'] = $closeable ?: sprintf(__('%s cannot be closed'), __('This ticket'));

        if ($errors)
            return false;

        $refer = $this->staff ?: $thisstaff;
        $this->closed = $this->lastupdate = SqlFunction::NOW();
        if ($thisstaff && $set_closing_agent)
            $this->staff = $thisstaff;
        // Clear overdue flags & due dates
        $this->clearOverdue(false);

        $ecb = function($t) use ($status) {
            $t->logEvent('closed', array('status' => array($status->getId(), $status->getName())), null, 'closed');
            $t->deleteDrafts();
        };
=====>  Signal::send('ticket.closed', $this);
        break;
#.... Next Code ....
```
## Result after an Enrollment Action
After a successfully enrollment action, the result of the action will be available in the Ticket as an Internal Note.

<img width="597" alt="image" src="https://github.com/raulnsuarez/OSTLimeSurveyPlugin/assets/29768685/3792144e-9c3f-48a4-b186-589c8113611e">

