<?php
/* Some convenience constants */
define('ROOTDIR', dirname(__FILE__));

/* Include Wordy API wrapper. */
include_once ROOTDIR . '/../library/Wordy/Api.php';
include_once ROOTDIR . '/../library/Wordy/Exception.php';

/* Initialize Wordy API for customer with Id: 1 */
$wordy = new Wordy_Api("b135f695dfc67b05105880ed3b549c3e", "06a0c25b0da2cfb9de323c0bd1724153", 1);

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
    <title>Wordy API v.2 example</title>
</head>
<body>
    
<?php if(empty($_POST) && array_key_exists("document_id", $_GET) === false): ?>
    <h1>Proofedit your post!</h1>
    <form action="" method="post">
        <p><label for="brief">Brief to editor: <br/><textarea id="brief" name="brief" rows="5" cols="80"></textarea></label></p>
        <p>
            <label for="language_code">Language: <br/>
                <select id="language_code" name="language_code">
                    <option value="GB">English (UK)</option>
                    <option value="US">English (US)</option>
                    <option value="DE">Deutsch</option>
                </select>
            </label>
        </p>
        <p><br/></p>
        <p><label for="post_title">Post title: <br/><input type="text" id="post_title" name="post_title" value="" size="80"/></label></p>
        <p><label for="post_content">Post content: <br/><textarea id="post_content" name="post_content" rows="10" cols="80"></textarea></label></p>
        <p><label for="post_excerpt">Post excerpt: <br/><textarea id="post_excerpt" name="post_excerpt" rows="3" cols="80"></textarea></label></p>
        <p><input type="submit" name="Submit" value="Proofread with Wordy!"/>
    </form>
<?php elseif(!empty($_POST)): ?>
    <?php
    
        try
        {
            /* Start the application session */
            $session = $wordy->application_startsession();
            if($session->success === true)
            {
                /* Create order */
                $response = $wordy->order_create($_POST['brief'], $_POST['language_code'], array(
                    array(
                        'title' => "post_title",
                        'type' => Wordy_Api::FIELD_SHORTTEXT,
                        'value' => $_POST['post_title']
                    ),
                    array(
                        'title' => "post_content",
                        'type' => Wordy_Api::FIELD_HTML,
                        'value' => $_POST['post_content']
                    ),
                    array(
                        'title' => "post_excerpt",
                        'type' => Wordy_Api::FIELD_LONGTEXT,
                        'value' => $_POST['post_excerpt']
                    )
                ));
                
                /* Check if response is OK. If it is - then redirect user to payment page. */
                if($response->success === true)
                {
                    ?>
                    <h1>Your order at Wordy</h1>
                    <p><strong>Order id: </strong> <?=$response->order->id?></p>
                    <p><a onclick="window.open(this.href); return false;" href="<?=$wordy->getPaymentUrl($response->order->id)?>">You should go and pay to complete the order</a></p>
                    <p>After payment: <a href="?document_id=<?=$response->order->documents[0]->id?>">click here</a> to see document information.</p>
                    <?php                    
                }

                $wordy->application_expiresession();
            }
            else
            {
                ?><h1>Could not start application session with Wordy.</h1><?php
            }
        }
        catch(Wordy_Exception $e)
        {
            echo "Exception: " . $e->getMessage();
        }
    ?>
<?php elseif(array_key_exists("document_id", $_GET)): ?>
    <?php
        try
        {
            $wordy->application_startsession();
            
            /* retrieve document information */
            $response = $wordy->document_info($_GET['document_id']);
            
            echo "<pre>";
            print_r($response);
            echo "</pre>";
            
            $wordy->application_expiresession();
        }
        catch(Exception $e)
        {
            echo "Exception: " . $e->getMessage();
        }
    ?>
<?php endif; ?>
    
</body>
</html>