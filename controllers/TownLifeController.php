<?php
require 'function.php';

const JWT_SECRET_KEY = "TEST_KEYTEST_KEYTEST_KEYTEST_KEYTEST_KEYTEST_KEYTEST_KEYTEST_KEYTEST_KEYTEST_KEYTEST_KEYTEST_KEYTEST_KEY";

$res = (Object)Array();
header('Content-Type: json');
$req = json_decode(file_get_contents("php://input"));
try {
    addAccessLogs($accessLogs, $req);
    switch ($handler) {
        case "index":
            echo "API Server";
            break;
        case "ACCESS_LOGS":
            //            header('content-type text/html charset=utf-8');
            header('Content-Type: text/html; charset=UTF-8');
            getLogs("./logs/access.log");
            break;
        case "ERROR_LOGS":
            //            header('content-type text/html charset=utf-8');
            header('Content-Type: text/html; charset=UTF-8');
            getLogs("./logs/errors.log");
            break;



        /* *********************************** 동네생활 ********************************************* */
        // 글조회
        case "getContent":
            http_response_code(200);
            $jwt = $_SERVER['HTTP_X_ACCESS_TOKEN'];
            $userIdxInToken = getDataByJWToken($jwt,JWT_SECRET_KEY)->userIdx;
            $category = $_GET['category'];
            $keyword = $_GET['keyword'];
            if (empty($jwt)){
                $res->isSuccess = FALSE;
                $res->code = 400;
                $res->message = "토큰을 입력하세요";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                addErrorLogs($errorLogs, $res, $req);
                return;
            }
            if (!isValidJWT($jwt, JWT_SECRET_KEY)) { // function.php 에 구현
                $res->isSuccess = FALSE;
                $res->code = 401;
                $res->message = "유효하지 않은 토큰입니다";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                addErrorLogs($errorLogs, $res, $req);
                return;
            }
            // 키워드 , 카테고리 검색 x
            if (empty($keyword) && empty($category)) {

                $arrayList = array();
                $townLifeIdx = getTownLifeIndex();
                while($townLifeIdx>0){
                    $temp=(Object)array();
                    if(isValidTownLife($townLifeIdx)){
                        if (!empty(getContentOne($townLifeIdx,$userIdxInToken))){ // 삭제해도되나
                            $temp=getContentOne($townLifeIdx,$userIdxInToken);
                            if(hasImage($townLifeIdx)) {
                                $i=0;
                                $img_arr=array();
                                $queryResult=getContentImg($townLifeIdx);
                                while($i<count($queryResult)){
                                    array_push($img_arr,$queryResult[$i++]['townLifePhotoUrl']);
                                }
                                $temp['img_arr']=$img_arr;
                            }
                            array_push($arrayList,$temp);
                        }

                    }
                    $townLifeIdx = $townLifeIdx-1;

                }
                $res->result = $arrayList;
                $res->isSuccess = TRUE;
                $res->code = 100;
                $res->message = "동네생활 글 조회 성공";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                break;
            }


            // 키워드 검색
            if (!empty($keyword) && empty($category)) {
                if (!isValidKeywordContent($keyword)){

                    $res->isSuccess = False;
                    $res->code = 201;
                    $res->message = "검색어에 관한 글 x";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    break;
                }
                $arrayList = array();
                $townLifeIdx = getTownLifeIndexKeyword($keyword);
                while($townLifeIdx>0){
                    $temp=(Object)array();
                    if(isValidTownLifeKeyword($townLifeIdx,$keyword)){
                        if(!empty(getKeywordContentOne($townLifeIdx,$keyword,$userIdxInToken))){
                            $temp=getKeywordContentOne($townLifeIdx,$keyword,$userIdxInToken);
                            if(hasImage($townLifeIdx)) {
                                $i=0;
                                $img_arr=array();
                                $queryResult=getContentImg($townLifeIdx);
                                while($i<count($queryResult)){
                                    array_push($img_arr,$queryResult[$i++]['townLifePhotoUrl']);
                                }
                                $temp['img_arr']=$img_arr;
                            }
                            array_push($arrayList,$temp);
                        }

                    }
                    $townLifeIdx = $townLifeIdx-1;

                }

                $res->result = $arrayList;
                $res->isSuccess = TRUE;
                $res->code = 101;
                $res->message = "동네생활 키워드 검색 성공";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                break;
            }

            // 글조회 3) 카테고리
            if (empty($keyword) && !empty($category)) {
                if (!isValidCategoryContent($category)){
                    $res->isSuccess = False;
                    $res->code = 202;
                    $res->message = "카테고리에 관한 글 x";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    break;
                }
                $arrayList = array();
                $townLifeIdx = getTownLifeIndexCategory($category);
                while($townLifeIdx>0){
                    $temp=(Object)array();
                    if(isValidTownLifeCategory($townLifeIdx,$category)){
                        if(!empty(getCategoryContentOne($townLifeIdx,$category,$userIdxInToken))){
                            $temp=getCategoryContentOne($townLifeIdx,$category,$userIdxInToken);
                            if(hasImage($townLifeIdx)) {
                                $i=0;
                                $img_arr=array();
                                $queryResult=getContentImg($townLifeIdx);
                                while($i<count($queryResult)){
                                    array_push($img_arr,$queryResult[$i++]['townLifePhotoUrl']);
                                }
                                $temp['img_arr']=$img_arr;
                            }
                            array_push($arrayList,$temp);
                        }

                    }
                    $townLifeIdx = $townLifeIdx-1;

                }



                $res->result = $arrayList;
                $res->isSuccess = TRUE;
                $res->code = 102;
                $res->message = "동네생활 카테고리 검색 성공";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                break;
            }

            //글조회 4) 둘다 비지 않았을때
            if (!empty($keyword) && !empty($category)) {
                if (!isValidCategoryContent($category)){
                    $res->isSuccess = False;
                    $res->code = 202;
                    $res->message = "카테고리에 관한 글 x";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    break;
                }
                if (!isValidKeywordContent($keyword)){

                    $res->isSuccess = False;
                    $res->code = 201;
                    $res->message = "검색어에 관한 글 x";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    break;
                }
                $arrayList = array();
                $townLifeIdx = getTownLifeIndexCategoryKeyword($category,$keyword);
                while($townLifeIdx>0){
                    $temp=(Object)array();
                    if(isValidTownLifeCategoryKeyword($townLifeIdx,$category,$keyword)){
                        if(!empty(getCategoryKeywordContentOne($userIdxInToken,$townLifeIdx,$category,$keyword))){
                            $temp=getCategoryKeywordContentOne($userIdxInToken,$townLifeIdx,$category,$keyword);
                            if(hasImage($townLifeIdx)) {
                                $i=0;
                                $img_arr=array();
                                $queryResult=getContentImg($townLifeIdx);
                                while($i<count($queryResult)){
                                    array_push($img_arr,$queryResult[$i++]['townLifePhotoUrl']);
                                }
                                $temp['img_arr']=$img_arr;
                            }
                            array_push($arrayList,$temp);
                        }

                    }
                    $townLifeIdx = $townLifeIdx-1;

                }


                $res->result = $arrayList;
                $res->isSuccess = TRUE;
                $res->code = 103;
                $res->message = "동네생활 카테고리 , 키워드 검색 성공";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                break;
            }


            break;



        case "getContentDetail":
            http_response_code(200);
            $jwt = $_SERVER['HTTP_X_ACCESS_TOKEN'];
            $userIdxInToken = getDataByJWToken($jwt,JWT_SECRET_KEY)->userIdx;
            if (empty($jwt)){
                $res->isSuccess = FALSE;
                $res->code = 400;
                $res->message = "토큰을 입력하세요";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                addErrorLogs($errorLogs, $res, $req);
                return;
            }
            if (!isValidJWT($jwt, JWT_SECRET_KEY)) { // function.php 에 구현
                $res->isSuccess = FALSE;
                $res->code = 401;
                $res->message = "유효하지 않은 토큰입니다";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                addErrorLogs($errorLogs, $res, $req);
                return;
            }
            if (!isValidTownLife($vars["contentIdx"])){
                $res->isSuccess = False;
                $res->code = 200;
                $res->message = "유효하지 않은 글";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                break;
            }

            $i=0;
            $img_arr=array();
            $queryResult=getContentImg($vars["contentIdx"]);
            while($i<count($queryResult)){
                array_push($img_arr,$queryResult[$i++]['townLifePhotoUrl']);
            }

            $res->result['content'] = getContentDetail($userIdxInToken,$vars["contentIdx"]);
            $res->result['contentImg'] =$img_arr;
            $res->result['comment'] =getComment($vars["contentIdx"],$userIdxInToken);
            $res->isSuccess = TRUE;
            $res->code = 100;
            $res->message = "글 세부 조회 성공";
            echo json_encode($res, JSON_NUMERIC_CHECK);
            break;
        // 동네생활 글 등록
        case "createContent":
            http_response_code(200);
            $jwt = $_SERVER['HTTP_X_ACCESS_TOKEN'];
            $userIdxInToken = getDataByJWToken($jwt,JWT_SECRET_KEY)->userIdx;
            if (empty($jwt)){
                $res->isSuccess = FALSE;
                $res->code = 400;
                $res->message = "토큰을 입력하세요";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                addErrorLogs($errorLogs, $res, $req);
                return;
            }
            if (!isValidJWT($jwt, JWT_SECRET_KEY)) { // function.php 에 구현
                $res->isSuccess = FALSE;
                $res->code = 401;
                $res->message = "유효하지 않은 토큰입니다";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                addErrorLogs($errorLogs, $res, $req);
                return;
            }
            if(empty($req->content)){
                $res->isSuccess = False;
                $res->code = 200;
                $res->message = "글을 입력하세요";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                break;
            }
            if(empty($req->categoryIdx)){
                $res->isSuccess = False;
                $res->code = 201;
                $res->message = "카테고리를 선택하세요";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                break;
            }
            if (count($req->imgList)>10){
                $res->isSuccess = False;
                $res->code = 202;
                $res->message = "사진 제한(10장) 초과";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                break;
            }

            if (!isContentCategory($req->categoryIdx)){
                $res->isSuccess = False;
                $res->code = 203;
                $res->message = "없는 카테고리입니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                break;
            }

            if(strlen($req->content)<8){
                $res->isSuccess = False;
                $res->code = 204;
                $res->message = "글의 길이가 짧습니다. 좀 더 길게 입력하세요.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                break;
            }

            $res->result = createContent($req->categoryIdx,$req->content,$req->imgList,$userIdxInToken);
            $res->isSuccess = TRUE;
            $res->code = 100;
            $res->message = "동네생활 글 등록 성공";
            echo json_encode($res, JSON_NUMERIC_CHECK|JSON_UNESCAPED_UNICODE);
            break;
        // 글 수정
        case "changeContent":
            http_response_code(200);
            // 해당유저의 페이로드에서 타운라이프 인덱스 가져와
            // 토큰속 타운라이프 인덱스와 이 패스배리어블인덱스같으면
            $jwt = $_SERVER['HTTP_X_ACCESS_TOKEN'];
            $userIdxInToken = getDataByJWToken($jwt,JWT_SECRET_KEY)->userIdx;
            if (empty($jwt)){
                $res->isSuccess = FALSE;
                $res->code = 400;
                $res->message = "토큰을 입력하세요";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                addErrorLogs($errorLogs, $res, $req);
                return;
            }
            if (!isValidJWT($jwt, JWT_SECRET_KEY)) { // function.php 에 구현
                $res->isSuccess = FALSE;
                $res->code = 401;
                $res->message = "유효하지 않은 토큰입니다";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                addErrorLogs($errorLogs, $res, $req);
                return;
            }
            if(!isValidChangeContent($userIdxInToken,$vars['contentIdx'])){
                $res->isSuccess = FALSE;
                $res->code = 300;
                $res->message = "권한이 없는 글입니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                break;
            }

            if (count($req->imgList)>10){
                $res->isSuccess = False;
                $res->code = 200;
                $res->message = "사진 제한(10장) 초과";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                break;
            }


            if(!empty($req->content)&strlen($req->content)<8){
                $res->isSuccess = False;
                $res->code = 201;
                $res->message = "글의 길이가 짧습니다. 좀 더 길게 입력하세요.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                break;
            }

            changeContent($vars['contentIdx'],$req->categoryIdx,$req->content,$req->imgList);
            $res->isSuccess = TRUE;
            $res->code = 100;
            $res->message = "동네생활 글 수정 성공";
            echo json_encode($res, JSON_NUMERIC_CHECK|JSON_UNESCAPED_UNICODE);
            break;


        case "clickContentSympathy":
            http_response_code(200);
            $jwt = $_SERVER['HTTP_X_ACCESS_TOKEN'];
            $userIdxInToken = getDataByJWToken($jwt,JWT_SECRET_KEY)->userIdx;
            if (empty($jwt)){
                $res->isSuccess = FALSE;
                $res->code = 400;
                $res->message = "토큰을 입력하세요";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                addErrorLogs($errorLogs, $res, $req);
                return;
            }
            if (!isValidJWT($jwt, JWT_SECRET_KEY)) { // function.php 에 구현
                $res->isSuccess = FALSE;
                $res->code = 401;
                $res->message = "유효하지 않은 토큰입니다";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                addErrorLogs($errorLogs, $res, $req);
                return;
            }
            if(!isValidTownLife($req->townLifeIdx)){ // 타운라피르 테이블에 인덱스 있는지
                $res->isSuccess = False;
                $res->code = 200;
                $res->message = "유효 하지 않은 글";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                break;
            }
            if(!isValidSympathy($req->sympathyIdx)){ // 공감종류테이블에 이값이 있는지
                $res->isSuccess = False;
                $res->code = 201;
                $res->message = "유효 하지 않은 공감";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                break;
            }
            if(isSympathyContent($req->townLifeIdx,$userIdxInToken)){ // 공감테이블에 프로덕트 인덱스 있는지
                deleteContentSympathy($req->townLifeIdx,$userIdxInToken);
                $res->isSuccess = False;
                $res->code = 202;
                $res->message = "공감 취소했습니다";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                break;
            }
            $res->result = clickContentSympathy($req->townLifeIdx,$req->sympathyIdx,$userIdxInToken);
            $res->isSuccess = TRUE;
            $res->code = 100;
            $res->message = "공감하기 성공";
            echo json_encode($res, JSON_NUMERIC_CHECK);
            break;

        case "getContentSympathy":
            http_response_code(200);
            $jwt = $_SERVER['HTTP_X_ACCESS_TOKEN'];
            $userIdxInToken = getDataByJWToken($jwt,JWT_SECRET_KEY)->userIdx;
            if (empty($jwt)){
                $res->isSuccess = FALSE;
                $res->code = 400;
                $res->message = "토큰을 입력하세요";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                addErrorLogs($errorLogs, $res, $req);
                return;
            }
            if (!isValidJWT($jwt, JWT_SECRET_KEY)) { // function.php 에 구현
                $res->isSuccess = FALSE;
                $res->code = 401;
                $res->message = "유효하지 않은 토큰입니다";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                addErrorLogs($errorLogs, $res, $req);
                return;
            }
            //$townLifeIdx = $vars['townLifeIdx'];
            if(!isValidTownLife($vars['contentIdx'])){
                $res->isSuccess = False;
                $res->code = 200;
                $res->message = "유효 하지 않은 글";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                break;
            }

            $res->result->count =getSympathyCount($vars['contentIdx']);
            $res->result->list = getContentSympathy($userIdxInToken,$vars['contentIdx']);
            $res->isSuccess = TRUE;
            $res->code = 100;
            $res->message = "글 공감 목록 유저 조회 성공";
            echo json_encode($res, JSON_NUMERIC_CHECK);
            break;

        case "clickCommentSympathy":
            http_response_code(200);
            $jwt = $_SERVER['HTTP_X_ACCESS_TOKEN'];
            $userIdxInToken = getDataByJWToken($jwt,JWT_SECRET_KEY)->userIdx;
            if (empty($jwt)){
                $res->isSuccess = FALSE;
                $res->code = 400;
                $res->message = "토큰을 입력하세요";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                addErrorLogs($errorLogs, $res, $req);
                return;
            }
            if (!isValidJWT($jwt, JWT_SECRET_KEY)) { // function.php 에 구현
                $res->isSuccess = FALSE;
                $res->code = 401;
                $res->message = "유효하지 않은 토큰입니다";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                addErrorLogs($errorLogs, $res, $req);
                return;
            }
            if(!isValidComment($req->commentIdx)){ // 댓글테이블에
                $res->isSuccess = False;
                $res->code = 203;
                $res->message = "유효하지않은 댓글";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                break;
            }
            if(!isValidSympathy($req->sympathyIdx)){ // 공감종류테이블에 이값이 있는지
                $res->isSuccess = False;
                $res->code = 201;
                $res->message = "유효 하지 않은 공감";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                break;
            }
            if(isSympathyComment($req->commentIdx,$userIdxInToken)){
                $res->result = deleteCommentSympathy($req->commentIdx,$userIdxInToken);
                $res->isSuccess = False;
                $res->code = 202;
                $res->message = "공감 취소했습니다";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                break;
            }

            $townLifeIdx=getTLIdx($req->commentIdx);
            $res->result = clickCommentSympathy($townLifeIdx,$req->sympathyIdx,$req->commentIdx,$userIdxInToken);
            $res->isSuccess = TRUE;
            $res->code = 100;
            $res->message = "댓글 공감하기 성공";
            echo json_encode($res, JSON_NUMERIC_CHECK);
            break;

        case "getCommentSympathy":
            http_response_code(200);
            $jwt = $_SERVER['HTTP_X_ACCESS_TOKEN'];
            $userIdxInToken = getDataByJWToken($jwt,JWT_SECRET_KEY)->userIdx;
            if (empty($jwt)){
                $res->isSuccess = FALSE;
                $res->code = 400;
                $res->message = "토큰을 입력하세요";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                addErrorLogs($errorLogs, $res, $req);
                return;
            }
            if (!isValidJWT($jwt, JWT_SECRET_KEY)) { // function.php 에 구현
                $res->isSuccess = FALSE;
                $res->code = 401;
                $res->message = "유효하지 않은 토큰입니다";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                addErrorLogs($errorLogs, $res, $req);
                return;
            }
            $commentIdx = $vars['commentIdx'];
            //$res->result = $vars['commentIdx'];
            if(!isValidComment($vars['commentIdx'])){
                $res->isSuccess = False;
                $res->code = 200;
                $res->message = "유효 하지 않은 댓글";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                break;
            }

            $res->result = getCommentSympathy($vars['commentIdx'],$userIdxInToken);
            $res->isSuccess = TRUE;
            $res->code = 100;
            $res->message = "댓글 공감 유저 조회 성공";
            echo json_encode($res, JSON_NUMERIC_CHECK);
            break;


        // 댓글 등록
        case "createComment":
            http_response_code(200);
            $jwt = $_SERVER['HTTP_X_ACCESS_TOKEN'];
            $userIdxInToken = getDataByJWToken($jwt,JWT_SECRET_KEY)->userIdx;
            if (empty($jwt)){
                $res->isSuccess = FALSE;
                $res->code = 400;
                $res->message = "토큰을 입력하세요";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                addErrorLogs($errorLogs, $res, $req);
                return;
            }
            if (!isValidJWT($jwt, JWT_SECRET_KEY)) { // function.php 에 구현
                $res->isSuccess = FALSE;
                $res->code = 401;
                $res->message = "유효하지 않은 토큰입니다";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                addErrorLogs($errorLogs, $res, $req);
                return;
            }
            if(empty($req->comment)){
                $res->isSuccess = False;
                $res->code = 200;
                $res->message = "댓글을 입력하세요";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                break;
            }

            $res->result = createComment($vars['contentIdx'], $req->comment,$req->photoUrl,$userIdxInToken);
            $res->isSuccess = TRUE;
            $res->code = 100;
            $res->message = "동네생활 댓글 등록 성공";
            echo json_encode($res, JSON_NUMERIC_CHECK|JSON_UNESCAPED_UNICODE);
            break;



        case "createCommentComment":
            http_response_code(200);
            $jwt = $_SERVER['HTTP_X_ACCESS_TOKEN'];
            $userIdxInToken = getDataByJWToken($jwt,JWT_SECRET_KEY)->userIdx;
            if (empty($jwt)){
                $res->isSuccess = FALSE;
                $res->code = 400;
                $res->message = "토큰을 입력하세요";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                addErrorLogs($errorLogs, $res, $req);
                return;
            }
            if (!isValidJWT($jwt, JWT_SECRET_KEY)) { // function.php 에 구현
                $res->isSuccess = FALSE;
                $res->code = 401;
                $res->message = "유효하지 않은 토큰입니다";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                addErrorLogs($errorLogs, $res, $req);
                return;
            }
            if(empty($req->comment)){
                $res->isSuccess = False;
                $res->code = 200;
                $res->message = "대댓글을 입력하세요";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                break;
            }

            $res->result = createCommentComment($vars['contentIdx'],$vars['commentIdx'], $req->comment,$req->photoUrl,$userIdxInToken);
            $res->isSuccess = TRUE;
            $res->code = 100;
            $res->message = "동네생활 대댓글 등록 성공";
            echo json_encode($res, JSON_NUMERIC_CHECK|JSON_UNESCAPED_UNICODE);
            break;

        //댓글 삭제
        case "deleteComment":
            http_response_code(200);

            $jwt = $_SERVER['HTTP_X_ACCESS_TOKEN'];
            $userIdxInToken = getDataByJWToken($jwt,JWT_SECRET_KEY)->userIdx;
            if (empty($jwt)){
                $res->isSuccess = FALSE;
                $res->code = 400;
                $res->message = "토큰을 입력하세요";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                addErrorLogs($errorLogs, $res, $req);
                return;
            }
            if (!isValidJWT($jwt, JWT_SECRET_KEY)) { // function.php 에 구현
                $res->isSuccess = FALSE;
                $res->code = 401;
                $res->message = "유효하지 않은 토큰입니다";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                addErrorLogs($errorLogs, $res, $req);
                return;
            }
            if(!isValidChangeComment($userIdxInToken,$vars['commentIdx'])){
                $res->isSuccess = FALSE;
                $res->code = 300;
                $res->message = "권한이 없는 댓글입니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                break;
            }
            if (!isValidComment($vars['commentIdx'])) { // 함수바꿔
                $res->isSuccess = False;
                $res->code = 200;
                $res->message = "유효 하지않은 값";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                break;
            }
            if (isDeletedComment($vars['commentIdx'])) {
                $res->isSuccess = False;
                $res->code = 201;
                $res->message = "이미 삭제된 값";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                break;
            }

            deleteComment($vars["commentIdx"],$userIdxInToken);
            $res->isSuccess = TRUE;
            $res->code = 100;
            $res->message = "댓글 삭제 성공";
            echo json_encode($res, JSON_NUMERIC_CHECK);
            break;


        // 글 삭제
        case "deleteContent":
            http_response_code(200);
            //$townLifeIdx=$vars['townLifeIdx']
            //$productIdx = $vars['townLifeIdx'];
            $jwt = $_SERVER['HTTP_X_ACCESS_TOKEN'];
            $userIdxInToken = getDataByJWToken($jwt,JWT_SECRET_KEY)->userIdx;
            if (empty($jwt)){
                $res->isSuccess = FALSE;
                $res->code = 400;
                $res->message = "토큰을 입력하세요";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                addErrorLogs($errorLogs, $res, $req);
                return;
            }
            if (!isValidJWT($jwt, JWT_SECRET_KEY)) { // function.php 에 구현
                $res->isSuccess = FALSE;
                $res->code = 401;
                $res->message = "유효하지 않은 토큰입니다";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                addErrorLogs($errorLogs, $res, $req);
                return;
            }
            if(!isValidChangeContent($userIdxInToken,$vars['contentIdx'])){
                $res->isSuccess = FALSE;
                $res->code = 300;
                $res->message = "권한이 없는 글입니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                break;
            }
            if (!isValidTownLife($vars['contentIdx'])) { // 함수바꿔
                $res->isSuccess = False;
                $res->code = 200;
                $res->message = "유효 하지않은 값";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                break;
            }
            if (isDeletedContent($vars['contentIdx'])) {
                $res->isSuccess = False;
                $res->code = 201;
                $res->message = "이미 삭제된 값";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                break;
            }

            deleteContent($vars["contentIdx"],$userIdxInToken);
            $res->isSuccess = TRUE;
            $res->code = 100;
            $res->message = "글 삭제 성공";
            echo json_encode($res, JSON_NUMERIC_CHECK);
            break;





    }
} catch (\Exception $e) {
    return getSQLErrorException($errorLogs, $e, $req);
}
