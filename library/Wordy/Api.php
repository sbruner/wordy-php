<?php
/**
 *  PHP wrapper class for Wordy API v.2.
 *
 *  @package Wordy
 *  @subpackage Api
 *
 *  @version 1
 *
 *  @author Julius Seporaitis <julius@seporaitis.net>
 *  @copyright Copyright (c) 2010, Julius Seporaitis
 *  @license http://www.apache.org/licenses/LICENSE-2.0
 *
 *  @link http://www.wordy.com/ Wordy ApS
 */

/**
 *  @package Wordy
 *  @subpackage Api
 */
class Wordy_Api
{
    /**
     *  Endpoint URL of Wordy API v.2
     *
     *  Can be one of:
     *
     *      * {@link http://www.wordy.com/api/version/2/ http://www.wordy.com/api/version/2/} for production use.
     *
     *      * {@link http://stage-wordyhq.flush.pil.dk/api/version/2/ http://stage-wordyhq.flush.pil.dk/api/version/2/} for testing/staging use (this is subject to change).
     *
     *      * {@link http://www.local.wordyhq.com/api/version/2/ http://www.local.wordyhq.com/api/version/2/} for development use (if you are Wordy developer).
     */
    const API_ENDPOINT = 'http://www.local.wordyhq.com/api/version/2/';
    
    const PAYMENT_ENDPOINT = 'http://www.local.wordyhq.com/order/new/pay/order_id/';
  
    /**
     *  Payment was just created, and there was no try to pay for the order yet.
     */
    const PAYMENT_NEW = 'payment_new';
    
    /**
     *  System is waiting for payment approval.
     *
     *  Usually this status is shown while user is filling up the payment form.
     */
    const PAYMENT_PENDING = 'payment_pending';
    
    /**
     *  System received payment. 
     *
     *  This status means that Wordy received the payment information and uploaded documents are placed for editing.
     */
    const PAYMENT_COMPLETED = 'payment_completed';
    
    /**
     *  Payment was refunded.
     *
     *  Usually this means that all documents in the placed order were cancelled.
     */
    const PAYMENT_REFUND = 'payment_refund';
  
    /**
     *  Document is created.
     *
     *  This status doesn't change until payment is done.
     *
     *  @todo Draw document state chart with GViz
     */
    const DOCUMENT_NEW = 'document_new';
    
    /**
     *  Document is open for editors.
     *
     *  This status means payment was made and document is visible for editors in the system to accept it for editing.
     */
    const DOCUMENT_OPEN = 'document_open';
    
    /**
     *  Document is being edited. 
     *
     *  This means document "pending" for editor to complete the editing process. Sometimes if editor is unsure if he
     *  can complete the task, he or she can cancel editing process and make the document again open for other editors
     *  to accept it.
     */
    const DOCUMENT_PENDING = 'document_pending';
    
    /**
     *  Document editing is completed.
     *
     *  This means that editor completed the editing process and sent the edited document back for customer approval.
     *
     *  When document is in this state, that means your application is able to download completed document data.
     *  {@see Wordy_Api::document_download()} 
     */
	const DOCUMENT_COMPLETED = 'document_completed';
	
	/**
	 *  Document is sent back for editor.
	 *
	 *  Almost same as DOCUMENT_PENDING, means that the document is being reclaimed and is "pending" for editor to do
	 *  the requested changes.
	 *  This status differs from DOCUMENT_PENDING in that editor can not drop (cancel) the document until he finishes
	 *  editing it.
	 */
	const DOCUMENT_RECLAIMED = 'document_reclaimed';
	
	/**
	 *  Document is closed and cannot be edited anymore.
	 *
	 *  This means that either customer itself wrote a review about the editing results or Wordy automatically closed
	 *  the document if it wasn't reclaimed for editing again after certain amount of days.
	 *
	 *  Thus as the document is closed, it's owner can only download document data ({@link Wordy_Api::document_download()})
	 *  but can not claim re-edit or cancel the document.
	 */
	const DOCUMENT_CLOSED = 'document_closed';
	
	/**
	 *  Document was cancelled.
	 *
	 *  This means that this job is no more available in the open jobs list for editors to take it and edit and the money
	 *  is refunded to customer.
	 */
	const DOCUMENT_CANCELED = 'document_canceled';
	
	/**
	 *  Document was killed.
	 *
	 *  This means that the document was killed by Wordy administrator. This happens in very rare cases:
	 *      * document was uploaded for translation (Wordy is copy-editing service).
	 *      * document file is malformed.
	 */
	const DOCUMENT_KILLED = 'document_killed';
  
    /**
     *  Document type text.
     *
     *  Text files are returned in JSON format when doing {@link Wordy_Api::document_download()}.
     */
    const TYPE_TEXT = 'text';
    
    /**
     *  Document type file.
     *
     *  If document is of type 'file', {@link Wordy_Api::document_download()} call will return file contents.
     */
    const TYPE_FILE = 'file';
  
    /**
     *  Short input field.
     *
     *  When sending field, set this type if the value of the field fits into a single line field. (e.g. post title).
     */
    const FIELD_SHORTTEXT = 'shorttext';
    
    /**
     *  Textarea
     *
     *  When sending field, set this type if the value of the field is multiline, but is not HTML formatted. (e.g. post excerpt).
     */
    const FIELD_LONGTEXT = 'longtext';
    
    /**
     *  HTML editor
     *
     *  Set this type for fields, which values are in HTML (e.g. post content).
     */
    const FIELD_HTML = 'html';

    /**
     *  Wordy API key hash.
     *
     *  @var string $_apiKey;
     */
    protected $_apiKey;
    
    /**
     *  Wordy API secret hash.
     *
     *  @var string $_apiSecret;
     */
    protected $_apiSecret;
    
    /**
     *  CURL connection resource
     *
     *  Used to do requests to the API endpoint.
     *
     *  @var resource $_connection
     */
    protected $_connection;
    
    /**
     *  Acquired session token
     *
     *  Used for signing requests ({@link Wordy_Api::_sign()}).
     *
     *  Also: {@see Wordy_Api::application_startsession()} {@see Wordy_Api::application_expiresession()}
     *
     *  @var string $_sessionToken
     */
    protected $_sessionToken;
    
    /**
     *  Customer Id
     *
     *  Identifies customer on whose behalf application is trying to run.
     *
     *  @var integer $_customerId
     */
    protected $_customerId;
    
    /**
     *  Sets variables and initializes CURL connection resource.
     *
     *  @param string $apiKey           Wordy API key
     *  @param string $apiSecret        Wordy API secret
     *  @param integer $customerId      Identifies customer on whose behalf application is trying to run.
     */
    public function __construct($apiKey, $apiSecret, $customerId)
    {
        $this->_apiKey = $apiKey;
        $this->_apiSecret = $apiSecret;
        $this->_customerId = $customerId;
    
        $this->_connection = curl_init();
    
        curl_setopt($this->_getConnection(), CURLOPT_HEADER, 0);
        curl_setopt($this->_getConnection(), CURLOPT_RETURNTRANSFER, 1);    
    }
    
    /**
     *  Returns some of Wordy API v.2 configuration variables.
     *  
     *  Example JSON response (though returned as stdObject, see: {@link Wordy_Api::request()}):
     *  <code>
     *  {"success": true, "wordPrice": "0.02", "currency": "EUR"}
     *  </code>
     *  
     *  @return stdClass
     */
    public function base_info()
    {
        $response = $this->request("/base/info/");
        $response = json_decode($response);
        return $response;
    }
    
    /**
     *  Returns an estimated delivery timestamp for the given word count.
     *
     *  Example JSON response (though returned as stdObject, see: {@link Wordy_Api::request()}):
     *  <code>
     *  {"success": true,
     *   "estimate": {
     *      "date_time": "2010-05-04 13:48:56",
     *      "nice_time": "1 hour 12 minutes"
     *  }}
     *  </code>
     *
     *  @param integer $word_count
     *  @return stdClass
     */
    public function base_estimate($word_count)
    {
        $response = $this->request("/base/estimate/word_count/" . intval($word_count));
        $response = json_decode($response);
        return $response;
    }
    
    /**
     *  Returns a little bit of Wordy statistics (as seen on front page).
     *
     *  Example JSON response (though returned as stdObject, see: {@link Wordy_Api::request()}):
     *  <code>
     *  {"success": true,
     *   "statistics": {
     *      "words_edited": 237433,
     *      "editors_count": 131,
     *      "estimated_time": "33 minutes per 400 words",
     *      "satisfaction": {
     *          "overall": 98,
     *          "excellent": 62,
     *          "good": 36,
     *          "poor": 2,
     *          "bad": 0
     *     }
     *  }}
     *  </code>
     *
     *  Fields are pretty much self explanatory:
     *      <ul>
     *          <li><b>words_edited</b> - number of words edited on Wordy.com</li>
     *          <li><b>editor_count</b> - number of approved editors on Wordy.com</li>
     *          <li><b>estimated_time</b> - how much time on average does it take to edit 400 word document.</li>
     *          <li><b>satisfaction</b> - Satisfaction numbers - overall and by rating (percentages).</li>
     *      </ul>
     *
     *  @return stdClass
     */
    public function base_statistics()
    {
        $response = $this->request("/base/statistics/");
        $response = json_decode($response);
        return $response;
    }
    
    /**
     *  Returns a random Wordy testimonial (as seen on front page).
     *
     *  Example JSON response (though returned as stdObject, see: {@link Wordy_Api::request()}):
     *  <code>
     *  {"success": true,
     *   "testimonial": {
     *      "author": "Elizabeth",
     *      "quote": "Thank you! Love the use of comments and suggestions! Great work.",
     *  }}
     *  </code>
     *
     *  @return stdClass
     */
    public function base_testimonial()
    {
        $response = $this->request("/base/testimonial/");
        $response = json_decode($response);
        return $response;
    }
    
    /**
     *  Starts a session in which your application can do actions on users behalf.
     *
     *  Example JSON response (though returned as stdObject, see: {@link Wordy_Api::request()}):
     *  <code>
     *  {"success": true,
     *   "session": {
     *      "api_key": "[your-api-key-hash-here]",
     *      "customer": {
     *          "id": 1,
     *          "email": "julius@seporaitis.net",
     *          "first_name": "Julius",
     *          "last_name": "Seporaitis"
     *      },
     *      "token": "[requested-session-token-hash]",
     *      "created_at": "2010-05-04 12:04:13",
     *      "expires_at": "2010-05-04 13:04:13"
     *  }}
     *  </code>
     *
     *  Fields are pretty much self explanatory:
     *      <ul>
     *          <li><b>customer</b> - holds customer, whose access you asked for, information.</li>
     *          <li><b>token</b> - this is the actual session identifier you were asking with this method.</li>
     *          <li><b>created_at</b> - session token creation date.</li>
     *          <li><b>expires_at</b> - session token expiration date.</li>
     *      </ul>
     *
     *  @param DateTime|string $expires_at = null    When should the API session expire (defaults to 1hour). Can be instance of DateTime or string in format "Y-m-d H:i:s".
     *  @return stdClass
     */
    public function application_startsession($expires_at = null)
    {
        $params = array('customer_id' => $this->_getCustomerId());
        if(null !== $expires_at)
        {
            if($expires_at instanceof DateTime)
            {
                $expires_at = $expires_at->format("Y-m-d H:i:s");
            }
            $params['expires_at'] = $expires_at;
        }

        $response = $this->request("/application/startsession/", $params);
        $response = json_decode($response);
        if($response->success == true)
        {
            $this->setSessionToken($response->session->token);
        }
            
        return $response;
    }
    
    /**
     *  Starts a session in which your application can do actions on users behalf.
     *
     *  Example JSON response (though returned as stdObject, see: {@link Wordy_Api::request()}):
     *  <code>
     *  {"success": true,
     *   "session": {
     *      "api_key": "[your-api-key-hash-here]",
     *      "customer": {
     *          "id": 1,
     *          "email": "julius@seporaitis.net",
     *          "first_name": "Julius",
     *          "last_name": "Seporaitis"
     *      },
     *      "token": "[requested-session-token-hash]",
     *      "created_at": "2010-05-04 12:04:13",
     *      "expires_at": "2010-05-04 13:04:13",
     *      "expired": true
     *  }}
     *  </code>
     *
     *  Fields are same as in {@link Wordy_Api::application_startsession()}, except:
     *  <ul>
     *      <li><b>expired</b> - holds value true and indicates that the session token is no longer valid.</li>
     *  </ul>
     *
     *  Usually this function should be used if you no longer need to work with Wordy API v.2
     *
     *  @return stdClass
     */
    public function application_expiresession()
    {
        $response = $this->request("/application/expiresession/", array('customer_id' => $this->_getCustomerId()));
        $response = json_decode($response);
        $this->setSessionToken(null);
        return $response;
    }
        
    /**
     *  Returns information about customer account, subscription package and current status of account resources.
     *
     *  Example JSON response (though returned as stdObject, see: {@link Wordy_Api::request()}):
     *  <code>
     *  {"success": true,
     *   "account": {
     *      "id": 1,
     *      "owner": {
     *          "id": 12,
     *          "email": "julius@seporaitis.net",
     *          "first_name": "Julius",
     *          "last_name": "Seporaitis"
     *      },
     *      "subscription": {
     *          "created_at": "2010-04-06 12:14:49",
     *          "type": "writer",
     *          "max_words": "11400",
     *          "max_users": "15",
     *          "max_editors": 15
     *      },
     *      "status": {
     *          "words_left": 9486,
     *          "balance": "165.58",
     *          "users_used": 10,
     *          "users_left": 5,
     *          "editors_used": 15,
     *          "editors_left": 0,
     *      }
     *  }}
     *  </code>
     *
     *  Fields worth mentioning:
     *  <ul>
     *      <li><b>expired</b> - holds value true and indicates that the session token is no longer valid.</li>
     *  </ul>
     *
     *  Usually this function should be used if you no longer need to work with Wordy API v.2
     *
     *  @return stdClass
     */
    public function account_info()
    {
        $response = $this->request("/account/info/", array('customer_id' => $this->_getCustomerId()));
        $response = json_decode($response);
        return $response;
    }
    
    /**
     *  Add user (either customer or editor) to account.
     *
     *  Example JSON response (though returned as stdObject, see: {@link Wordy_Api::request()}) when user_id is editor:
     *  <code>
     *  {"success": true,
     *   "user": {
     *      "id": 391,
     *      "type": "editor",
     *      "first_name": "Anders",
     *      "last_name": "S"
     *      "categories": "Academic, Business & Corporate, Education, Fiction",
     *      "subjects": "Business, Computing, Economics, Education, Law, Lifestyle, Literature"
     *  }}
     *  </code>
     *
     *  Example JSON response (though returned as stdObject, see: {@link Wordy_Api::request()}) when user_id is customer:
     *  <code>
     *  {"success": true,
     *   "user": {
     *      "id": 1,
     *      "type": "customer",
     *      "first_name": "Julius",
     *      "last_name": "Seporaitis"
     *      "email": "julius@seporaitis.net"
     *  }}
     *  </code>
     *
     *  Note that for editor users only the first letter of the last name is returned and email is omitted in response.
     *
     *  @param integer $user_id         Id if customer or editor which is added to account.
     *  @return stdClass
     */
    public function account_adduser($user_id)
    {
        $response = $this->request("/account/adduser/", array('customer_id' => $this->_getCustomerId(), 'user_id' => $user_id));
        $response = json_decode($response);
        return $response;
    }
    
    /**
     *  Remove user (either customer or editor) from account.
     *
     *  Example JSON response (though returned as stdObject, see: {@link Wordy_Api::request()}) when user_id is editor:
     *  <code>
     *  {"success": true,
     *   "user": {
     *      "id": 391,
     *      "type": "editor",
     *      "first_name": "Anders",
     *      "last_name": "S"
     *      "categories": "Academic, Business & Corporate, Education, Fiction",
     *      "subjects": "Business, Computing, Economics, Education, Law, Lifestyle, Literature"
     *  }}
     *  </code>
     *
     *  Example JSON response (though returned as stdObject, see: {@link Wordy_Api::request()}) when user_id is customer:
     *  <code>
     *  {"success": true,
     *   "user": {
     *      "id": 1,
     *      "type": "customer",
     *      "first_name": "Julius",
     *      "last_name": "Seporaitis"
     *      "email": "julius@seporaitis.net"
     *  }}
     *  </code>
     *
     *  Note that for editor users only the first letter of the last name is returned and email is omitted in response.
     *
     *  @param integer $user_id         Id if customer or editor which is removed from account.
     *  @return stdClass
     */
    public function account_removeuser($user_id)
    {
        $response = $this->request("/account/removeuser/", array('customer_id' => $this->_getCustomerId(), 'user_id' => $user_id));
        $response = json_decode($response);
        return $response;
    }
    
    /**
     *  Returns a list of customers and editors linked to account.
     *
     *  Example JSON response (though returned as stdObject, see: {@link Wordy_Api::request()}) when user_id is editor:
     *  <code>
     *  {"success": true,
     *   "customers": [
     *      {"id": 1,
     *       "type": "customer",
     *       "first_name": "Julius",
     *       "last_name": "Seporaitis"
     *       "email": "julius@seporaitis.net"},
     *      {"id": 2,
     *       "type": "customer",
     *       "first_name": "Name",
     *       "last_name": "Nameson"
     *       "email": "name@nameson.me"}
     *    ],
     *    "editors": [
     *      {"id": 391,
     *      "type": "editor",
     *      "first_name": "Anders",
     *      "last_name": "S"
     *      "categories": "Academic, Business & Corporate, Education, Fiction",
     *      "subjects": "Business, Computing, Economics, Education, Law, Lifestyle, Literature"}
     *    ]
     *  }
     *  </code>
     *
     *  Note that for editor users only the first letter of the last name is returned and email is omitted in response.
     *
     *  @return stdClass
     */
    public function account_users()
    {
        $response = $this->request("/account/users/", array('customer_id' => $this->_getCustomerId()));
        $response = json_decode($response);
        return $response;
    }
    
    /**
     *  Create an order with a document that can have any number of fields (current limitation is up to 10 fields, but can be bigger if required).
     *
     *  Field in this call is a triple of:
     *      <ul>
     *          <li><b>title</b> - a field name, shown for an editor in Wordy, so he or she can understand the purpose of the field.</li>
     *          <li><b>type</b> - can be one of: shorttext, longtext or html. Depending on the type - the editor in Wordy will see different input 
     *                              fields for content editing. Also see {@link Wordy_Api::FIELD_SHORTTEXT}, {@link Wordy_Api::FIELD_LONGTEXT} and
     *                              {@link Wordy_Api::FIELD_HTML}.</li>
     *          <li><b>value</b> - this is the actual value of the field.
     *      </ul>
     *
     *  For a Wordpress post <i>$fields</i> parameter array would look like:
     *  <code>
     *      $fields = array(
     *          array(
     *              'title' => "post_title",                // Label for editors will be shown as "Post title".
     *              'type' => Wordy_Api::FIELD_SHORTTEXT,   // Titles usually fit into one line, so we choose <i>shorttext</i> type
     *              'value' => "A word about Wordy"         // The actual post title
     *          ),
     *          array(
     *              'title' => "post_content",              // Label for editors will be shown as "Post content".
     *              'type' => Wordy_Api::FIELD_HTML,        // Usually Wordpress blog posts are HTML.
     *              'value' => "Post body in <strong>HTML</strong>."
     *          ),
     *          array(
     *              'title' => "post_excerpt",              // Label for editors will be shown as "Post excerpt".
     *              'type' => Wordy_Api::FIELD_LONGTEXT,    // Excerpt in Wordpress is usually long plain text, so we chose <i>longtext</i> type.
     *              'value' => "This is a short intro..."
     *          )
     *      );
     *  </code>
     *
     *  Also you can pass additional metadata to add to the document. Metadata is a key => value array which is later returned when doing the
     *  {@link Wordy_Api::document_info()} call.
     *  Metadata array example:
     *  <code>
     *      $metadata = array(
     *          'user_id' => "247",                 // This is user id in application using API.
     *          'user_email' => "user@userland.org",// This is user email in application using API.
     *          'other_data' => "some_other_data",  // any other data you want to add
     *      );
     *  </code>
     *  
     *  It is recommended that you add user_id and user_email in your system (as in the example above).
     *
     *  Example JSON response (though returned as stdObject, see: {@link Wordy_Api::request()}):
     *  <code>
     *  {"success": true,
     *   "order": {
     *      "id": 7948,
     *      "approximate_time": "2010-12-08 17:46:54",
     *      "word_count": 13,
     *      "price": 3.0,
     *      "payment_status": "payment_new",
     *      "documents": [
     *          {"id": 8425,
     *          "word_count": 13,
     *          "approximate_time": "2010-12-08 17:46:54",
     *          "price": 3.0,
     *          "language_code": "GB",
     *          "status_code": "document_new",
     *          "metadata": {
     *              "user_id": "247",
     *              "user_email": "user@userland.org",
     *              "other_data": "some_other_data"
     *          }}
     *      ]
     *  }}
     *  </code>
     *  
     *  @param string $brief                Note for the editor.
     *  @param string $language_code        Language code. Can be "GB" for UK English, "US" for US English or "DE" for Deutsch documents.
     *  @param array $fields                Array with fields. See examples above.
     *  @param array $metadata              Key-value array with any additional metadata.
     *  @return stdClass
     */
    public function order_create($brief, $language_code, array $fields, array $metadata = array())
    {
        if(count($fields) === 0)
        {
            return null;
        }
        
        $params = array(
            'customer_id' => $this->_getCustomerId(),
            'brief' => $brief,
            'language_code' => $language_code,
        );
        $fields = array_values($fields); /* make sure keys are numeric */
        
        foreach($fields as $idx => $field)
        {
            /* skip invalid fields */
            if(!in_array($field['type'], array(self::FIELD_SHORTTEXT, self::FIELD_LONGTEXT, self::FIELD_HTML)) ||
                empty($field['title']) ||
                empty($field['value'])) continue;
                
            $params['title' . ($idx+1)] = $field['title'];
            $params['type' . ($idx+1)] = $field['type'];
            $params['value' . ($idx+1)] = $field['value'];
        }
            
        if(count($metadata) > 0)
        {
            $idx = 1;
            foreach($metadata as $key => $value)
            {
                if(empty($key) || empty($value)) continue;
                
                $params['metaname' . ($idx)] = $key;
                if(is_array($value) === true || is_object($value) === true)
                {
                    $params['metavalue'] = json_encode($value);
                }
                else
                {
                    $params['metavalue' . ($idx)] = $value;
                }
                $idx++;
            }
        }
            
        $response = $this->request("/order/create/", $params);
        $response = json_decode($response);
        return $response;
    }
    
    /**
     *  Retrieves document information object.
     *
     *  Example JSON response (though returned as stdObject, see: {@link Wordy_Api::request()}):
     *  <code>
     *  {"success": true,
     *   "document": {
     *      "id": 8425,
     *      "type": "text",
     *      "word_count": 13,
     *      "created_at": "2010-12-08 17:40:54",
     *      "edited_at": null,
     *      "completed_at": null,
     *      "approximate_time": "2010-12-08 17:46:54",
     *      "price": 3.0,
     *      "language_code": "GB",
     *      "status_code": "document_open",
     *      "metadata": {
     *          "user_id": "247",
     *          "user_email": "user@userland.org",
     *          "other_data": "some_other_data"
     *      }}
     *  }
     *  </code>
     *
     *  This call is usually used to check the document <b>status_code</b> attribute for changes.
     *
     *  @param integer $document_id         Id of document to retrieve info for.
     *  @return stdClass
     */
    public function document_info($document_id)
    {
        $response = $this->request("/document/info/", array('customer_id' => $this->_getCustomerId(), 'id' => $document_id));
        $response = json_decode($response);
        return $response;
    }
    
    /**
     *  Returns actual document contents.
     *
     *  Depending on the <b>document.type</b> attribute (see {@link Wordy_Api::document_info()} example response) will return either file contents
     *  (for document with type "file") or a decoded JSON object (if document type is "text").
     *
     *  Example JSON response (though returned as stdObject when <b>document.type</b> is "file", see: {@link Wordy_Api::request()}):
     *  <code>
     *  {"success": true,
     *   "fields": [
     *      {"title": "post_title",
     *       "type": "shorttext",
     *       "value": "A word about Wordy"},
     *      {"title": "post_content",
     *       "type": "html",
     *       "value": "Post body in <strong>HTML</strong>."},
     *      {"title": "post_excerpt",
     *       "type": "longtext",
     *       "value": "This is a short introduction..."}
     *  ]}
     *  </code>
     *
     *  @param integer $document_id         Id of downloadable document
     *  @return stdClass|string
     */
    public function document_download($document_id)
    {
        $response = $this->request("/account/info/", array('customer_id' => $this->_getCustomerId(), 'id' => $document_id));
        $response = json_decode($response);
        if($response->success === true)
        {
            $document = $this->request("/document/download/", array('customer_id' => $this->_getCustomerId(), 'id' => $document_id));
            if($response->document->type == self::TYPE_TEXT)
            {
                return json_decode($document);
            }
            elseif($response->document->type == self::TYPE_FILE)
            {
                return $document;
            }
        }
        return $response;
    }
    
    /**
     *  Cancel the document and receive a refund.
     *
     *  Note, that document cancelling is possible <i>only if</i> document status is "document_open", that is no editor taken the job.
     *
     *  Example JSON response (though returned as stdObject, see: {@link Wordy_Api::request()}):
     *  <code>
     *  {"success": true}
     *  </code>
     *
     *  @param integer $document_id         Id of document to cancel.
     *  @return stdClass
     */
    public function document_cancel($document_id)
    {
        $response = $this->request("/document/cancel/", array('customer_id' => $this->_getCustomerId(), 'id' => $document_id));
        $response = json_decode($response);
        return $response;
    }
    
    /**
     *  Claims a document re-edit.
     *
     *  If customer is unhappy with edited document, he claims re-edit and editor has to look into document one more time.
     *
     *  Example JSON response (though returned as stdObject, see: {@link Wordy_Api::request()}):
     *  <code>
     *  {"success": true}
     *  </code>
     *
     *  @param integer $document_id         Id of document to claim re-edit for.
     *  @param string $message              Message for editor
     *  @return stdClass
     */
    public function document_reedit($document_id, $message)
    {
        $response = $this->request("/document/reedit/", array('customer_id' => $this->_getCustomerId(), 'id' => $document_id, 'message' => $message));
        $response = json_decode($response);
        return $response;
    }
    
    /**
     *  Creates a customer.
     *
     *  Example JSON response (though returned as stdObject, see: {@link Wordy_Api::request()}):
     *  <code>
     *  {"success": true,
     *   "customer": {
     *      "id": 1,
     *      "email": "julius@seporaitis.net",
     *      "first_name": "Julius",
     *      "last_name": "Seporaitis",
     *      "company_name": "Wordy"
     *  }
     *  </code>  
     *  
     *  @param string $email                    Customer email address
     *  @param string $password                 Password
     *  @param string $confirm                  Password confirmation
     *  @param string $first_name               First name
     *  @param string $last_name                Last name
     *  @param string $country_code             Country code (two digit country code).
     *  @param string $company_name = ""        Company name
     *  @return stdClass
     */
    public function customer_create($email, $password, $confirm, $first_name, $last_name, $country_code, $company_name = "")
    {
        $response = $this->request("/customer/create/", array('email' => $email, 
                                                              'password' => $password,
                                                              'confirm' => $confirm,
                                                              'first_name' => $first_name,
                                                              'last_name' => $last_name,
                                                              'country_code' => $country_code,
                                                              'company_name' => $company_name), false);
        $response = json_decode($response);
        return $response;
    }
    
    /**
     *  Retrieve authenticated user information.
     *
     *  Example JSON response (though returned as stdObject, see: {@link Wordy_Api::request()}):
     *  <code>
     *  {"success": true,
     *   "customer": {
     *      "id": 1,
     *      "email": "julius@seporaitis.net",
     *      "first_name": "Julius",
     *      "last_name": "Seporaitis",
     *      "company_name": "Wordy"
     *  }
     *  </code>  
     *
     *  @return stdClass
     */
    public function customer_info()
    {
        $response = $this->request("/customer/info/", array('customer_id' => $this->_getCustomerId()));
        $response = json_decode($response);
        return $response;
    }
    
    /**
     *  Do a request to the Wordy API v.2.
     *
     *  Receives a JSON string, <i>json_decode</i>'s it and returns stdObject.
     *
     *  In case of being unable to complete the request - throws an Wordy_Exception.
     *
     *  @throws Wordy_Exception             when unable to complete request/receive response.
     *  @param string $request              Request URI
     *  @param array $params = array()      POST parameter array
     *  @param boolean $sign = true         Should this request be signed?
     *  @return stdObject
     */
    public function request($request, array $params = array(), $sign = true)
    {
        curl_setopt($this->_getConnection(), CURLOPT_POST, 0);
        if(!empty($params) && true === $sign)
        {
            curl_setopt($this->_getConnection(), CURLOPT_POST, 1);
            $signature = "/api_key/" . $this->_getApiKey() . "/signature/" . $this->_sign($params) . "/";
            if($this->getSessionToken() !== null) $signature = rtrim($signature, "/") . "/token/" . $this->getSessionToken() . "/";
            $request = rtrim($request, "/") . $signature;
        }
    
        curl_setopt($this->_getConnection(), CURLOPT_URL, self::API_ENDPOINT . ltrim($request, "/"));
        curl_setopt($this->_getConnection(), CURLOPT_POSTFIELDS, $params);

        $result = curl_exec($this->_getConnection());
        if($result === false)
        {
            throw new Wordy_Exception("Request failed.");
        }
    
        return $result;
    }
    
    /**
     *  Return full url of payment page.
     *
     *  @param integer $order_id                Id of order to pay for.
     */
    public function getPaymentUrl($order_id)
    {
        return (self::PAYMENT_ENDPOINT . $order_id . "/");
    }
    
    /**
     *  Set API session token.
     *
     *  @param string $token
     *  @return Wordy_Api
     */
    public function setSessionToken($token)
    {
        $this->_sessionToken = (string) $token;
        return $this;
    }

    /**
     *  Get API session token.
     *
     *  @return string
     */
    public function getSessionToken()
    {
        return $this->_sessionToken;
    }

    /**
     *  Return API key hash
     *
     *  @return string
     */
    protected function _getApiKey()
    {
        return $this->_apiKey;
    }

    /**
     *  Return API secret hash
     *
     *  @return string
     */
    protected function _getApiSecret()
    {
        return $this->_apiSecret;
    }
    
    /**
     *  Return customer Id
     *
     *  @return integer
     */
    protected function _getCustomerId()
    {
        return $this->_customerId;
    }
  
    /**
     *  Return the token used for signing requests.
     *
     *  If user has started an application session (with {@link Wordy_Api::application_startsession()}) then signing token is
     *  the received session token. Otherwise it is API secret hash.
     *
     *  @return string
     */
    protected function _getSigningToken()
    {
        if($this->getSessionToken() === null)
        {
            return $this->_getApiSecret();
        }
      
        return $this->getSessionToken();
    }

    /**
     *  Return CURL connection resource.
     *
     *  @return resource
     */
    protected function _getConnection()
    {
        return $this->_connection;
    }

    /**
     *  Generate signature for given parameters
     *
     *  @return string
     */
    protected function _sign(array $params = array())
    {
        ksort($params);

        $str = "";
        foreach($params as $key => $value)
        {
            $str .= $key . "=" . $value;
        }

        return md5($str . $this->_getSigningToken());
    }
}
