<?php

function isValidUser($ID, $pwd)
{
    $pdo = pdoSqlConnect();
    $query = "SELECT id, pwd as hash FROM User WHERE id= ?;";


    $st = $pdo->prepare($query);
    $st->execute([$ID]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st=null;
    $pdo = null;

    return password_verify($pwd, $res[0]['hash']);

}

function isValidUser2($ID){
    $pdo = pdoSqlConnect();
    $query = "SELECT EXISTS(SELECT id FROM User WHERE id= ?) AS exist;";


    $st = $pdo->prepare($query);
    $st->execute([$ID]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st=null;
    $pdo = null;

    return intval($res[0]["exist"]);

}

function getUserIdxByID($ID)
{
    $pdo = pdoSqlConnect();
    $query = "SELECT userIdx FROM User WHERE id = ?;";

    $st = $pdo->prepare($query);
    $st->execute([$ID]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return $res[0]['userIdx'];
}
//function kakaoLogin($accessToken)
//{
//
//    $USER_API_URL= "https://kapi.kakao.com/v2/user/me";
//    $opts = array( CURLOPT_URL => $USER_API_URL,
//        CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSLVERSION => 1,
//        CURLOPT_POST => true,
//        CURLOPT_POSTFIELDS => false,
//        CURLOPT_RETURNTRANSFER => true,
//        CURLOPT_HTTPHEADER => array( "Authorization: Bearer " . $accessToken ) );
//
//    $curlSession = curl_init();
//    curl_setopt_array($curlSession, $opts);
//    $accessUserJson = curl_exec($curlSession);
//    curl_close($curlSession);
//
//    $me_responseArr = json_decode($accessUserJson, true);
//    if ($me_responseArr['id']) {
//        $mb_uid = $me_responseArr['id'];
//        //$nickname = 'kakao_'.$me_responseArr['nickname'];
//        $registered_at = $me_responseArr['registered_at'];
//        $mb_nickname = $me_responseArr['properties']['nickname']; // 닉네임
//        $mb_profile_image = $me_responseArr['properties']['profile_image']; // 프로필 이미지
//        $mb_thumbnail_image = $me_responseArr['properties']['thumbnail_image']; // 프로필 이미지
//        $mb_email = $me_responseArr['kakao_account']['email']; // 이메일
//        $mb_gender = $me_responseArr['kakao_account']['gender']; // 성별 female/male
//        $mb_age = $me_responseArr['kakao_account']['age_range']; // 연령대
//        $mb_birthday = $me_responseArr['kakao_account']['birthday']; // 생일
//
//    }
//    $pdo = pdoSqlConnect();
//
//    $query = "insert into socialLoginDB(nickname,img) values (?,?)";
//    $st = $pdo->prepare($query);
//    $st->execute([$mb_nickname,$mb_thumbnail_image]);
//    $st = null;
//    $pdo = null;
//    return '성공';
//
//}

function kakaoLogin($accessToken)
{

    $USER_API_URL= "https://kapi.kakao.com/v2/user/me";
    $opts = array( CURLOPT_URL => $USER_API_URL,
        CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSLVERSION => 1,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => false,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => array( "Authorization: Bearer " . $accessToken ) );

    $curlSession = curl_init();
    curl_setopt_array($curlSession, $opts);
    $accessUserJson = curl_exec($curlSession);
    curl_close($curlSession);

    $me_responseArr = json_decode($accessUserJson, true);
    if ($me_responseArr['id']) {
        $mb_uid = 'kakao_'.$me_responseArr['id'];
        $mb_nickname = $me_responseArr['properties']['nickname']; // 닉네임
        $mb_profile_image = $me_responseArr['properties']['profile_image']; // 프로필 이미지
//        $mb_thumbnail_image = $me_responseArr['properties']['thumbnail_image']; // 프로필 이미지


    }
    $pdo = pdoSqlConnect();

    $query = "insert into User(nickname,profilePhotoUrl,id) values (?,?,?);";
    $st = $pdo->prepare($query);
    $st->execute([$mb_nickname,$mb_profile_image,$mb_uid]);
    $userIdx=$pdo->lastInsertId();
    $st = null;
    $pdo = null;
    return $userIdx;

}

function kakaoLogin2($mb_uid)
{

    $pdo = pdoSqlConnect();

    $query = "select userIdx from User where id=? and isDeleted='N';";
    $st = $pdo->prepare($query);
    $st->execute([$mb_uid]);
    $userIdx=$pdo->lastInsertId();
    $st = null;
    $pdo = null;
    return $userIdx;

}

function createSignUpJwt($nickname,$phoneNumber,$profilePhotoUrl,$lat,$lon,$si,$gu,$dong,$id,$pwd_hash)
{
    $pdo = pdoSqlConnect();
    $query = "INSERT INTO User (nickname,phoneNumber,profilePhotoUrl,lat,lon,si,gu,dong,id,pwd)
              VALUES (?,?,?,?,?,?,?,?,?,?);";

    $st = $pdo->prepare($query);
    $st->execute([$nickname,$phoneNumber,$profilePhotoUrl,$lat,$lon,$si,$gu,$dong,$id,$pwd_hash]);
    $userIdx =  $pdo->lastInsertId();
    $st = null;
    $pdo = null;
    return ['userIdx'=>$userIdx];
}