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
            // 이게 로그 관련 그건가?

        // 중고 물품 조회
        case "getProduct":
            $jwt = $_SERVER['HTTP_X_ACCESS_TOKEN'];
            $userIdxInToken = getDataByJWToken($jwt,JWT_SECRET_KEY)->userIdx;
            http_response_code(200);
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
            $excludedCategory = $_GET['excluded-category'];
            $keyword = $_GET['keyword'];
//            $res->keyword = gettype($keyword);
////            $res->excludedCategory = gettype($excludedCategory); // 배열에 원소가 인티저여야함
//            echo json_encode($res, JSON_NUMERIC_CHECK);
//
//            if (gettype($keyword)!='string'){
//                $res->isSuccess = False;
//                $res->code = 500;
//                $res->message = "맞지않는 데이터 타입 ";
//                echo json_encode($res, JSON_NUMERIC_CHECK);
//                break;
//            }
//            break;
            if (empty($keyword) && empty($excludedCategory)) {
                $res->result = getProduct($userIdxInToken);
            }
            else if (!empty($keyword) && empty($excludedCategory)) {
                //validation
                if (!isValidKeyword($keyword)){

                    $res->isSuccess = False;
                    $res->code = 201;
                    $res->message = "검색어에 관한 상품 x";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    break;
                }
                $res->result = getKeywordProducts($keyword,$userIdxInToken);
            }

            else if (empty($keyword) && !empty($excludedCategory)) {
                //validation
                if (!isValidCategory($excludedCategory)){ //수정
                    $res->isSuccess = False;
                    $res->code = 202;
                    $res->message = "카테고리에 관한 상품 x";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    break;
                }
                $res->result = getCategoryProducts($excludedCategory,$userIdxInToken);
            }
            else{
                //validation
                if (!isValidKeyword($keyword)){

                    $res->isSuccess = False;
                    $res->code = 201;
                    $res->message = "검색어에 관한 상품 x";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    break;
                }
                if (!isValidCategory($excludedCategory)){ //수정
                    $res->isSuccess = False;
                    $res->code = 202;
                    $res->message = "카테고리에 관한 상품 x";
                    echo json_encode($res, JSON_NUMERIC_CHECK);
                    break;
                }
                $res->result = getKeywordCategoryProducts($keyword, $excludedCategory,$userIdxInToken);
            }
            $res->isSuccess = TRUE;
            $res->code = 100;
            $res->message = "중고 거래 물품 조회 성공";
            echo json_encode($res, JSON_NUMERIC_CHECK);
            break;



        case "getProductDetail":
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
            if (!isValidProduct($vars["productIdx"])){
                $res->isSuccess = False;
                $res->code = 200;
                $res->message = "유효 하지않은 물품";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                break;
            }
//            if (!isValidUser($vars["productIdx"])){
//                $res->isSuccess = False;
//                $res->code = 201;
//                $res->message = "유효하지않은 유저의 게시글";
//                echo json_encode($res, JSON_NUMERIC_CHECK);
//                break;
//            }


            $i=0;
            $img_arr=array();
            $queryResult=getProductImg($vars["productIdx"]);
            while($i<count($queryResult)){
                array_push($img_arr,$queryResult[$i++]['productPhotoUrl']);
            }

            $res->result= getProductDetail($vars["productIdx"],$userIdxInToken);
            $res->result['productImg'] =$img_arr;
            $res->isSuccess = TRUE;
            $res->code = 100;
            $res->message = "물품 세부 내용 조회 성공";
            echo json_encode($res, JSON_NUMERIC_CHECK);
            break;


        // 중고거래 물품 등록
        case "createProduct":
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
//            if (gettype($req->productDetail)!='string'|gettype($req->title)!='string'|gettype($req->imgList)!='array'
//                |gettype($req->price)!='integer'|gettype($req->categoryIdx)!='integer'|gettype($req->priceDeal)!='string'){
//                $res->isSuccess = False;
//                $res->code = 201;
//                $res->message = "맞지않는 데이터 타입 ";
//                echo json_encode($res, JSON_NUMERIC_CHECK);
//                break;
//            }

            if (empty($req->title)){
                $res->isSuccess = False;
                $res->code = 201;
                $res->message = "제목을 입력하세요";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                break;
            }
            if(empty($req->productDetail)){
                $res->isSuccess = False;
                $res->code = 202;
                $res->message = "설명을 입력하세요";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                break;
            }
            if(empty($req->categoryIdx)){
                $res->isSuccess = False;
                $res->code = 203;
                $res->message = "카테고리를 선택하세요";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                break;
            }
            if (count($req->imgList)>10){
                $res->isSuccess = False;
                $res->code = 204;
                $res->message = "사진 제한(10장) 초과";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                break;
            }
            if(strlen($req->productDetail)<10){
                $res->isSuccess = False;
                $res->code = 205;
                $res->message = "글의 길이가 짧습니다. 좀 더 길게 입력하세요.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                break;
            }
//            $res->result1=gettype($req->title);//string
//            $res->result2=gettype($req->price);//integer
//            $res->result3=gettype($req->productDetail);//string
//            $res->result4=gettype($req->categoryIdx);//integer
//            $res->result5=gettype($req->imgList);//array
//            $res->result6=gettype($req->priceDeal);//string
//            $res->result7=gettype($userIdxInToken);//string

            $res->result->productIdx = createProduct($req->title,$req->price,$req->productDetail,$req->categoryIdx,
                $req->imgList,$req->priceDeal,$userIdxInToken);
            $res->isSuccess = TRUE;
            $res->code = 100;
            $res->message = "중고거래 물품 등록 성공";
            echo json_encode($res, JSON_NUMERIC_CHECK|JSON_UNESCAPED_UNICODE);
            break;



        // 중고거래 물품 수정
        case "changeProduct":
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
//            if (gettype($req->productDetail)!='string'|gettype($req->title)!='string'|gettype($req->imgList)!='array'
//                |gettype($req->price)!='integer'|gettype($req->categoryIdx)!='integer'|gettype($req->priceDeal)!='string'){
//                $res->isSuccess = False;
//                $res->code = 201;
//                $res->message = "맞지않는 데이터 타입 ";
//                echo json_encode($res, JSON_NUMERIC_CHECK);
//                break;
//            }
            if(!isValidChangeProduct($userIdxInToken,$vars['productIdx'])){
                $res->isSuccess = FALSE;
                $res->code = 300;
                $res->message = "권한이 없는 글입니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                break;
            }
            if(!empty($req->productDetail)&strlen($req->productDetail)<10){
                $res->isSuccess = False;
                $res->code = 200;
                $res->message = "글의 길이가 짧습니다. 좀 더 길게 입력하세요.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                break;
            }


            if (count($req->imgList)>10){
                $res->isSuccess = False;
                $res->code = 201;
                $res->message = "사진 제한(10장) 초과";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                break;
            }


            changeProduct($vars['productIdx'], $req->title,$req->price,$req->productDetail,$req->categoryIdx,$req->imgList,$req->priceDeal);
            $res->isSuccess = TRUE;
            $res->code = 100;
            $res->message = "중고거래 물품 수정 성공";
            echo json_encode($res, JSON_NUMERIC_CHECK|JSON_UNESCAPED_UNICODE);
            break;

        case "upProduct":
            http_response_code(200);
            // 내가 내것만 끌어올리기 가능
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
            if(!isValidChangeProduct($userIdxInToken,$vars['productIdx'])){
                $res->isSuccess = FALSE;
                $res->code = 300;
                $res->message = "권한이 없는 글입니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                break;
            }
            if (!isValidProduct($vars['productIdx'])) {
                $res->isSuccess = False;
                $res->code = 200;
                $res->message = "유효 하지않은 값";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                break;
            }
            if (isUpY($vars['productIdx'])) {
                $res->isSuccess = False;
                $res->code = 201;
                $res->message = "끌어올리기한 상태";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                break;
            }
            upProduct($vars["productIdx"]);
            $res->isSuccess = TRUE;
            $res->code = 100;
            $res->message = "물품 끌어올리기 성공";
            //$res->message = upProduct($vars["productIdx"]);
            echo json_encode($res, JSON_NUMERIC_CHECK);
            break;



        case "interestProduct":
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
            // 가져온토큰을 어떻게 처리할지 넘기긴 하는데 밸리데이션
            if(!isValidProduct($req->productIdx)){ // product 테이블에 인덱스 있는지
                $res->isSuccess = False;
                $res->code = 200;
                $res->message = "유효 하지 않은 인덱스";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                break;
            }

            if(isInterest($req->productIdx,$userIdxInToken)){
                $res->result=deleteInterest($req->productIdx,$userIdxInToken);
                $res->isSuccess = TRUE;
                $res->code = 201;
                $res->message = "관심 취소했습니다";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                break;
            }
            $res->result = interestProduct($req->productIdx,$userIdxInToken);
            $res->isSuccess = TRUE;
            $res->code = 100;
            $res->message = "관심목록 추가 성공";
            echo json_encode($res, JSON_NUMERIC_CHECK);
            break;



        case "deleteProduct":
            http_response_code(200);

            //토큰의 유저 인덱스와 삭제하려는 이 물품의 유저인덱스가 같으면 삭제 다르면 밸리데이션
            // 이상품의 유저 인덱스를 불러와서 if 문 처리
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
            if(!isValidToken($userIdxInToken,$vars['productIdx'])){
                $res->isSuccess = FALSE;
                $res->code = 300;
                $res->message = "권한이 없는 글입니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                break;
            }

            if (isDeletedY($vars['productIdx'])) {
                $res->isSuccess = False;
                $res->code = 201;
                $res->message = "이미 삭제된 값";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                break;
            }

            deleteProduct($vars["productIdx"]);
            $res->isSuccess = TRUE;
            $res->code = 100;
            $res->message = "물품 삭제 성공";
            echo json_encode($res, JSON_NUMERIC_CHECK);
            break;




    }
} catch (\Exception $e) {
    return getSQLErrorException($errorLogs, $e, $req);
}
