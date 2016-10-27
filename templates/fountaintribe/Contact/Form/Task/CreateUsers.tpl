{*

*}

<div class="crm-block crm-form-block crm-contact-task-addtogroup-form-block">

<p>This will create active users that will be able to login to this website. If an individual already has an associated user ID, then nothing will be done for that individual. Only Individuals that have a first name, last name, and email address will get an associated user created. <br><br>
The email sent to the user can be <a href='/admin/config/people/accounts' target='_new'>CONFIGURED</a><br>

<!-- Drupal 6 url: '/admin/user/settings' -->
                Change the section labeled "Welcome, new user created by administrator"   </p>
  
  <table class="form-layout">
      {*    <tr><td colspan=2>{ts 1=$totalSelectedContacts}Number of selected contacts to create users for: %1{/ts}</td></tr>  *}
          <tr><td width=75 >
        
         </td></tr>
       
         
          {if $form.notify_user_of_password}
                <tr class="crm-contact-task-createpledge-form-block-group_type">
		    <td class="label" width=255>{$form.notify_user_of_password.label}</td>
                    <td width=500>{$form.notify_user_of_password.html}</td>
                </tr>
                
         {/if}
         
          {if $form.verification_only}
                <tr class="crm-contact-task-createpledge-form-block-group_type">
		    <td class="label" width=255>{$form.verification_only.label}</td>
                    <td width=500>{$form.verification_only.html}</td>
                </tr>
         {/if}
            
         
  </table>
  <p><h2>USE THIS FEATURE WITH CAUTION</h2>
      <br>This feature creates a security weakness because it creates active (enabled) user names and passwords that
       can be used to login to this website, for people who did not request such access. Many people for whom users have been created may have NO interest in logging in and will never change their password.  Yet the existance of these users creates an opporunity for a hacker to try to login using these credentials.
       <br><br>
       Security best practice is to encourage people to fill out the form at <a href='/user/register' target='_new'>/user/register</a>
       
       </p>
  
  <br><br>
  <table>
   <tr><td>{include file="CRM/Contact/Form/Task.tpl"}</td></tr>
   </table>
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>