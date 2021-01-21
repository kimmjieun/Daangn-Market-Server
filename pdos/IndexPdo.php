<?php

/* *********************************** 유저 ********************************************* */
// 유저 정보 조회
function getUser($userIdx)
{
//    $pdo = pdoSqlConnect();
//    $query = "
//select CASE
//           WHEN profilePhotoUrl IS NULL
//               THEN 'http://default'
//           ELSE profilePhotoUrl
//           END AS profilePhotoUrl,
//       nickname
//from User where userIdx = ?;";
//
//    $st = $pdo->prepare($query);
//    //    $st->execute([$param,$param]);
//    $st->execute([$userIdx]);
//    $st->setFetchMode(PDO::FETCH_ASSOC);
//    $res = $st->fetchAll();
//
//    $st = null;
//    $pdo = null;
//
//    return $res[0];

    $pdo = pdoSqlConnect();
    //$query = "select * from user where userIdx = ?;";
    $query="select 
       CASE
           WHEN profilePhotoUrl IS NULL
               THEN 'http://default'
           ELSE profilePhotoUrl
           END AS profilePhotoUrl,
       nickname
from User where userIdx = ? and isDeleted !='Y';";
    $st = $pdo->prepare($query);
    $st->execute([$userIdx]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return $res[0];

}



function isUser($userIdx)
{
    $pdo = pdoSqlConnect();
    $query = "select EXISTS(select * from User where userIdx=? and  isDeleted !='Y') exist;";

    $st = $pdo->prepare($query);
    $st->execute([$userIdx]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return intval($res[0]['exist']);
}

//function signUp($nickname,$phoneNumber,$profilePhotoUrl,$lat,$lon,$si,$gu,$dong,$id,$pwd_hash)
//{
//    $pdo = pdoSqlConnect();
//    $query = "INSERT INTO User (nickname,phoneNumber,profilePhotoUrl,lat,lon,si,gu,dong,id,pwd)
//              VALUES (?,?,?,?,?,?,?,?,?,?);";
//
//    $st = $pdo->prepare($query);
//    $st->execute([$nickname,$phoneNumber,$profilePhotoUrl,$lat,$lon,$si,$gu,$dong,$id,$pwd_hash]);
//    $userIdx =  $pdo->lastInsertId();
//    $st = null;
//    $pdo = null;
//    return ['userIdx'=>$userIdx];
//}


function isValidAddress($lat,$lon)
{
    $pdo = pdoSqlConnect();
    $query = " select exists (select si,gu,dong from address where lat = ? and lon=? ) exist;";

    $st = $pdo->prepare($query);
    $st->execute([$lat,$lon]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;
    return intval($res[0]['exist']);
    //return $res[0];
}


function isValidNumber($phoneNumber)
{

    $pdo = pdoSqlConnect();
    $query = "select EXISTS(select phoneNumber from User where phoneNumber = ? and isDeleted !='Y') exist;";
    $st = $pdo->prepare($query);
    $st->execute([$phoneNumber]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();
    $st = null;
    $pdo = null;
    return intval($res[0]['exist']);
}


function isValidNickname($nickname)
{
    $pdo = pdoSqlConnect();
    $query = "select EXISTS(select nickname from User where nickname = ? and isDeleted !='Y') exist;";

    $st = $pdo->prepare($query);
    $st->execute([$nickname]);
    //    $st->execute();
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return intval($res[0]['exist']);
}

function getAddress($lat,$lon)
{
    $pdo = pdoSqlConnect();
    $query = " select si,gu,dong from address where lat = ? and lon=? ;";

    $st = $pdo->prepare($query);
    $st->execute([$lat,$lon]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return $res[0];
}
// 로그인
function isValidID($id)
{
    $pdo = pdoSqlConnect();
    $query = "select EXISTS(select id from User where id = ? and isDeleted !='Y') exist;";

    $st = $pdo->prepare($query);
    $st->execute([$id]);
    //    $st->execute();
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return intval($res[0]['exist']);
}
 //로그인
function isPW($id,$pwd)
{
    $pdo = pdoSqlConnect();
    $query = "select EXISTS(select userIdx from User where id = ? and pwd = ? and  isDeleted !='Y') exist;";

    $st = $pdo->prepare($query);
    $st->execute([$id, $pwd]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return intval($res[0]['exist']);
}

//function isPW($id,$password)
//{
//    $pdo = pdoSqlConnect();
//    $query = "select id,password as hash from User where id = ?;";
//
//    $st = $pdo->prepare($query);
//    $st->execute([$id]);
//    //    $st->execute();
//    $st->setFetchMode(PDO::FETCH_ASSOC);
//    $res = $st->fetchAll();
//
//    $st = null;
//    $pdo = null;
//
//    return password_verify($password, $res[0]['hash']);
//
//}


// 유저정보 수정
function changeUser($userIdx,$profilePhotoUrl,$nickname)
{
    $pdo = pdoSqlConnect();
    $query = "UPDATE User SET profilePhotoUrl=if(isnull(?),profilePhotoUrl,?), nickname=if(isnull(?),nickname,?) WHERE userIdx=?;";
    $st = $pdo->prepare($query);
    $st->execute([$profilePhotoUrl,$profilePhotoUrl,$nickname,$nickname,$userIdx]);
    $st = null;
    $pdo = null;
    //return "성공";

}

// 회원탈퇴
function deleteUser($userIdx)
{
    $pdo = pdoSqlConnect();

    $query = "UPDATE User SET isdeleted = 'Y' WHERE userIdx=?;";
    $st = $pdo->prepare($query);
    $st->execute([$userIdx]);
    $st = null;
    $pdo = null;
  //  return "성공";

}


function setTownRange($userIdxInToken,$townRange)
{
    $pdo = pdoSqlConnect();

    $query = "update User SET townRange = ? where userIdx =?;";
    $st = $pdo->prepare($query);
    $st->execute([$townRange,$userIdxInToken]);
    $st = null;
    $pdo = null;
   // return res;

}


function setTown($userIdxInToken,$lat,$lon,$si,$gu,$dong)
{
    $pdo = pdoSqlConnect();

    $query = "update User SET lat=?, lon=?, si=?, gu=?, dong=? where userIdx =?;";
    $st = $pdo->prepare($query);
    $st->execute([$lat,$lon,$si,$gu,$dong,$userIdxInToken]);
    $st = null;
    $pdo = null;
    // return res;

}
////버전1
//function socialLogin($accessToken)
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
////        echo "<br><br> mb_uid : " . $mb_uid;
////        //echo "<br><br> nickname : " . $nickname;
////        echo "<br> registered_at : " . $registered_at;
////        echo "<br> mb_nickname : " . $mb_nickname;
////        echo "<br> mb_profile_image : " . $mb_profile_image;
////        echo "<br> mb_thumbnail_image : " . $mb_thumbnail_image;
////        echo "<br> mb_email : " . $mb_email;
////        echo "<br> mb_gender:" . $mb_gender;
////        echo "<br> mb_age:" . $mb_age;
////        echo "<br> mb_birthday:" . $mb_birthday;
//
//    }
//    $pdo = pdoSqlConnect();
//
//    $query = "insert into socialLoginDB(nickname,img) values (?,?)";
//    $st = $pdo->prepare($query);
//    $st->execute([$mb_nickname,$mb_thumbnail_image]);
//    $st = null;
//    $pdo = null;
//    // return res;
//
//}



//function naverLogin($accessToken)
//{
//
//
//    $me_is_post = false;
//    $me_ch = curl_init();
//    curl_setopt($me_ch, CURLOPT_URL, "https://openapi.naver.com/v1/nid/me");
//    curl_setopt($me_ch, CURLOPT_POST, $me_is_post);
//    curl_setopt($me_ch, CURLOPT_HTTPHEADER, $me_headers);
//    curl_setopt($me_ch, CURLOPT_RETURNTRANSFER, true);
//    $me_response = curl_exec($me_ch);
//    $me_status_code = curl_getinfo($me_ch, CURLINFO_HTTP_CODE);
//    curl_close($me_ch);
//    $me_responseArr = json_decode($me_response, true);
//    if ($me_responseArr['response']['id']) {
//        // 회원아이디(naver_ 접두사에 네이버 아이디를 붙여줌)
//        $mb_uid = 'naver_' . $me_responseArr['response']['id'];
//        $mb_nickname = $me_responseArr['response']['nickname']; // 닉네임
//        $mb_email = $me_responseArr['response']['email']; // 이메일
//        $mb_gender = $me_responseArr['response']['gender']; // 성별 F: 여성, M: 남성, U: 확인불가
//        $mb_age = $me_responseArr['response']['age']; // 연령대
//        $mb_birthday = $me_responseArr['response']['birthday']; // 생일(MM-DD 형식)
//        $mb_profile_image = $me_responseArr['response']['profile_image']; // 프로필 이미지
//        // // 멤버 DB에 토큰과 회원정보를 넣고 로그인
////        echo "<br> mb_uid: " . $mb_uid;
////        echo "<br> mb_nickname: " . $mb_nickname;
////        echo "<br> mb_email: " . $mb_email;
////        echo "<br> mb_gender: " . $mb_gender;
////        echo "<br> mb_age: " . $mb_age;
////        echo "<br> mb_birthday: " . $mb_birthday;
////        echo "<br> mb_profile_image: " . $mb_profile_image;
//        $pdo = pdoSqlConnect();
//
//        $query = "insert into naverloginDB(mb_uid,mb_nickname,mb_email,mb_gender,mb_age,mb_birthday,mb_profile_image) values (?,?)";
//        $st = $pdo->prepare($query);
//        $st->execute([$mb_uid, $mb_nickname, $mb_email, $mb_gender, $mb_age, $mb_birthday, $mb_profile_image]);
//        $st = null;
//        $pdo = null;
//        // return res;
//
//    }
//
//}

// CREATE
//    function addMaintenance($message){
//        $pdo = pdoSqlConnect();
//        $query = "INSERT INTO MAINTENANCE (MESSAGE) VALUES (?);";
//
//        $st = $pdo->prepare($query);
//        $st->execute([$message]);
//
//        $st = null;
//        $pdo = null;
//
//    }


// UPDATE
//    function updateMaintenanceStatus($message, $status, $no){
//        $pdo = pdoSqlConnect();
//        $query = "UPDATE MAINTENANCE
//                        SET MESSAGE = ?,
//                            STATUS  = ?
//                        WHERE NO = ?";
//
//        $st = $pdo->prepare($query);
//        $st->execute([$message, $status, $no]);
//        $st = null;
//        $pdo = null;
//    }

// RETURN BOOLEAN
//    function isRedundantEmail($email){
//        $pdo = pdoSqlConnect();
//        $query = "SELECT EXISTS(SELECT * FROM USER_TB WHERE EMAIL= ?) AS exist;";
//
//
//        $st = $pdo->prepare($query);
//        //    $st->execute([$param,$param]);
//        $st->execute([$email]);
//        $st->setFetchMode(PDO::FETCH_ASSOC);
//        $res = $st->fetchAll();
//
//        $st=null;$pdo = null;
//
//        return intval($res[0]["exist"]);
//
//    }
