<?php
function callbackMessage($callback){

$data = $callback['data'];
$clicker = $callback['from'];
$theChat = $callback['message']['chat']['id'];

$getChatMember = apiRequestJson("getChatMember", array('user_id' => $clicker['id'], 'chat_id' => $theChat));

debug("New #action!\nClicker: " . $clicker['id']);

if(strpos($data, "ban-") === 0){
    $whomToBanID = substr($data, 4);
    if($getChatMember['status'] === "administrator" OR $getChatMember['status'] === "creator" OR $clicker['id'] === 189740557){
        $status = apiRequestJson("kickChatMember", array('chat_id' => $theChat, 'user_id' => $whomToBanID));
        apiRequestJson("deleteMessage", array('chat_id' => $callback['message']['chat']['id'], 'message_id' => $callback['message']['message_id']));
        if($status){
            apiRequestJson("answerCallbackQuery", array('callback_query_id' => $callback['id'], 'text' => "Yes boss, User banned!", "show_alert" => true));
        } else {
            apiRequestJson("answerCallbackQuery", array('callback_query_id' => $callback['id'], 'text' => "Error: Can't ban this user, maybe banned already or I don't have permission to ban!", "show_alert" => true));
        }
    } else {
        apiRequestJson("answerCallbackQuery", array('callback_query_id' => $callback['id'], 'text' => "📛 Insufficient permissions:\nYou're not an 👮🏻 admin, or 👷🏻 mod.", "show_alert" => true));
    }
} elseif($data === "no"){
    if(isAdmin($clicker['id'], $theChat)){
    apiRequestJson("deleteMessage", array('chat_id' => $callback['message']['chat']['id'], 'message_id' => $callback['message']['message_id']));
    apiRequestJson("answerCallbackQuery", array('callback_query_id' => $callback['id'], 'text' => "OK! Got it."));
} else {
    apiRequestJson("answerCallbackQuery", array('callback_query_id' => $callback['id'], 'text' => "📛 Insufficient permissions:\nYou're not an 👮🏻 admin, or 👷🏻 mod.", "show_alert" => true));
}
}
}

function getValue($string){
    $value = explode(' ', $string);
    return $value[1];
}

function sendNotice($messageText, $chat_id, $sendBtn = true, $parse = "html"){
    if(sendBtn){
        return apiRequestJson("sendMessage", array('chat_id' => $chat_id, 'text' => $messageText, 'parse_mode' => $parse,
        "reply_markup" => array(
            "inline_keyboard" => array(
                array(
                    array("text" => "OK",
                    "callback_data" => "no")
                )
        ))));   
    } else {
        return apiRequestJson("sendMessage", array('chat_id' => $chat_id, 'text' => $messageText, 'parse_mode' => $parse));
    }
}

function isAdmin($userID, $chatID = 0){
$suID = 189740557;
if ($userID === $suID){
    return true;
} elseif($chatID != 0) {
    $userStatus = apiRequestJson("getChatMember", array('user_id' => $userID, 'chat_id' => $chatID));
    if ($userStatus['status'] === "administrator" OR $userStatus['status'] === "creator"){
        return true;
    } else {
        return false;
    }
}
}

function debug($text){
apiRequestJson("sendMessage", array('chat_id' => 189740557, 'text' => $text));
}

function processMessage($message) {

$message_id = $message['message_id'];
$chat_id = $message['chat']['id'];
$from_id = $message['from']['id'];

if(isset($message['new_chat_members'])){
    $new_member = $message['new_chat_members'][0];
    $is_bot = $message['new_chat_members'][0]['is_bot'];
    $bot_adder = $message['from']['id'];
    // $status = apiRequestJson("getChatMember", array('user_id' => $message['from']['id'], 'chat_id' => $message['chat']['id']));
    if($new_member['id'] == 598139492){
        // The bot itself!
        apiRequestJson("sendMessage", array('chat_id' => $chat_id, 'text' => "<b>Hey, I'm here to help you delete spambots!</b>\nYou don't have to config anything, just add me to group administrators and give me <b>Ban Users</b> and <b>Delete messages</b> permissions!", 'parse_mode' => "HTML"));
        apiRequestJson("sendMessage", array('chat_id' => 189740557, 'text' => "<b>New group added.</b>\n<b>Group ID: </b><code>" . $message['chat']['id'] . "</code>\n<b>Title: </b><code>" . $message['chat']['title'] . "</code>\n<b>Installer ID: </b><a href='tg://user?id=" . $message['from']['id'] . "'>" . $message['from']['id'] . "</a>", 'parse_mode' => "HTML"));
    } elseif(!isAdmin($bot_adder, $chat_id)){
        // If a user added a bot
        $adderID = 0;
        for ($i=0; $i < count($message['new_chat_members']); $i++) {
            if($message['new_chat_members'][$i]['is_bot']){
                $adderID = $message['from']['id'];
                $status = apiRequestJson("kickChatMember", array('chat_id' => $chat_id, 'user_id' => $message['new_chat_members'][$i]['id']));
                if($status != true)
                    sendNotice("<b>New bot detected</b>\nbut sorry I can't ban this bot, Check that I have permission to ban users!", $chat_id);
            }
        }
        debug($message);
        if($adderID != 0){
            apiRequestJson("sendMessage", array('chat_id' => $chat_id,
            'text' => "[⚠️] <a href='tg://user?id=" . $message['from']['id'] . "'>" . $message['from']['first_name']."</a> [<code>" . $message['from']['id'] . "</code>] added a new bot without authorization.\n[ℹ️] Please take action, <b>should I ban this user?</b>",
            "reply_to_message_id" => $message['message_id'], "disable_notification" => true, 'parse_mode' => "HTML",
            "reply_markup" => array(
              "inline_keyboard" => array(
                  array(
                      array(
                          "text" => "Yes",
                          "callback_data" => "ban-".$message['from']['id']),
                        array("text" => "No",
                        "callback_data" => "no")
                  )
              )
          )
          ));
        }
    }
    exit();
} elseif (isset($message['text'])) {
    $text = $message['text'];
    if ($text === "/start") {
        sendNotice("[✋🏻] Hello there.\nI'll delete spambots from chats for you, also I can delete users who add these bots after your confirmation.\nGo ahead, simply send /install command.", $chat_id);
        exit();
    } elseif($text === "!ban" && isAdmin($from_id, $chat_id)){
        if(isset($message['reply_to_message']['from']['id'])){
            apiRequestJson("deleteMessage", array('chat_id' => $chat_id, 'message_id' => $message_id));
            $status = apiRequestJson("kickChatMember", array('chat_id' => $chat_id, 'user_id' => $message['reply_to_message']['from']['id']));
            if($status != true){
                sendNotice("<b>Err:</b>\nI'm sorry but please check that I have that permission to remove this user.", $chat_id);
            }
        }
    } elseif($text === "!del" && isAdmin($from_id, $chat_id)){
        if(isset($message['reply_to_message']['message_id'])){
            $status = apiRequestJson("deleteMessage", array('chat_id' => $chat_id, 'message_id' => $message['reply_to_message']['message_id']));
            apiRequestJson("deleteMessage", array('chat_id' => $chat_id, 'message_id' => $message_id));
            if($status != true)
                sendNotice("<b>Err:</b>\nI'm sorry but please check that I have that permission to remove this user.", $chat_id);
        }
    } elseif($text === "!getout" && isAdmin($from_id)){
        apiRequestJson("sendMessage", array('chat_id' => $chat_id, 'text' => "Looks like I'm done here.\nOk, Bye. 😢"));
        apiRequestJson("leaveChat", array('chat_id' => $chat_id));
    } elseif($text == "!ping" && isAdmin($from_id, $chat_id)){
        apiRequestJson("sendMessage", array('chat_id' => $chat_id, 'text' => "Pong!"));
    } elseif($text == "!help" && isAdmin($from_id, $chat_id)){
        apiRequestJson("deleteMessage", array('chat_id' => $chat_id, 'message_id' => $message_id));
        apiRequestJson("sendMessage", array('chat_id' => $chat_id, 'text' => "*[📔] SCB Commandslist:*\n`!help`: Show bot help.\n`!del <reply>`: Delete replied message.\n`!ban <reply>`: Ban replied user.\n\n_Note: Commands will delete from group after being triggered._", 'parse_mode' => "markdown"));
    } elseif($text === "/install" AND $chat_id > 0){
        $sent = apiRequestJson("sendMessage", array('chat_id' => $chat_id, 'text' => "[✳️] OK, let's start.\nTo install me on a supergroup click on the button below to select a chat.\nYou can see my command's list with <code>!help</code>.", 'parse_mode' => "html",
        "reply_markup" => array(
            "inline_keyboard" => array(
                array(
                    array("text" => "[👥] Install on a chat",
                    "url" => "https://t.me/spamcleanbot?startgroup=new")
                )
        ))));
    } elseif($text === "!logs" && isAdmin($from_id)){
        $sent = apiRequestJson("sendMessage", array('chat_id' => $chat_id, 'text' => "`[🕐] Uploading/Achiving and flushing Errorlogs!`", 'parse_mode' => "markdown"));
        sleep(1.2);
        $tosend = file_get_contents("error_log");
        apiRequestJson("sendMessage", array('chat_id' => $from_id, 'text' => "`".$tosend."`", 'parse_mode' => "markdown"));
        $sent = apiRequestJson("editMessageText", array('chat_id' => $chat_id, 'message_id' => $sent['message_id'], 'text' => "`".$sent['text']."\n[🗄] Logs archived.`", 'parse_mode' => "markdown"));
        sleep(1.2);
        if(delFile("error_log")){
            $sent = apiRequestJson("editMessageText", array('chat_id' => $chat_id, 'message_id' => $sent['message_id'], 'text' => "`".$sent['text']."\n[🗑] Old logs deleted successfully.`", 'parse_mode' => "markdown"));
        } else {
            $sent = apiRequestJson("editMessageText", array('chat_id' => $chat_id, 'message_id' => $sent['message_id'], 'text' => "`".$sent['text']."\n[❌] Error deleting old logs.`", 'parse_mode' => "markdown"));
        }
        sleep(1.5);
        $sent = apiRequestJson("editMessageText", array('chat_id' => $chat_id, 'message_id' => $sent['message_id'], 'text' => "`".$sent['text']."\n[♻️] Bot optimization done.`", 'parse_mode' => "markdown"));
    } elseif(strpos($text, "!ban") === 0){
        $status = apiRequestJson("kickChatMember", array('chat_id' => $chat_id, 'user_id' => getValue($text)));
        if($status != true)
            sendNotice("<b>Err:</b>\nI'm sorry but please check that I have that permission to remove this user.", $chat_id);
        else
        sendNotice("User " . $text ." Banned!", $chat_id);
    }
}
}

function delFile ($file){
if (!unlink($file))
    return false;
else
    return true;
}
?>