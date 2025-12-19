# API 연결 디버깅 가이드

## 문제 상황
리뷰, 문의, 주문, 회원 4개 API가 배포 서버에서 데이터를 불러오지 못함

## 추가된 디버깅 로그

각 API 파일에 상세한 로깅을 추가했습니다:

### 1. `/api/admin/users.php`
- 요청 시작/종료 로그
- 세션 상태 확인
- 관리자 권한 체크 결과
- DB 쿼리 실행 시간
- 조회된 회원 수

### 2. `/api/inquiries.php`
- 요청 시작/종료 로그
- 세션 상태 확인
- 관리자 권한 체크 결과
- DB 쿼리 실행 시간
- 조회된 문의 수

### 3. `/api/reviews.php`
- 요청 시작/종료 로그
- 세션 상태 확인
- 관리자 권한 체크 결과 (관리자 모드일 때)
- DB 쿼리 실행 시간
- 조회된 리뷰 수

### 4. `/api/orders.php`
- 요청 시작/종료 로그
- 세션 상태 확인
- 관리자 권한 체크 결과
- SQL 쿼리 및 파라미터
- DB 쿼리 실행 시간
- 조회된 주문 수

## 확인 방법

### PHP error_log 확인
서버의 PHP error_log 파일에서 다음 로그를 확인하세요:

```
[Admin Users API] ========== 요청 시작 ==========
[Admin Users API] Session ID: ...
[Admin Users API] isAdmin: true/false
[Admin Users API] DB query 완료: XXXms, Users count: X
```

### 브라우저 개발자 도구
1. Network 탭에서 각 API 요청 확인
   - `/api/admin/users.php`
   - `/api/inquiries.php`
   - `/api/reviews.php`
   - `/api/orders.php`
2. 응답 상태 코드 확인 (200, 401, 500 등)
3. 응답 본문 확인 (에러 메시지가 있는지)

## 가능한 원인

1. **세션 문제**: 세션이 서버에서 유지되지 않아 권한 체크 실패
   - 확인: error_log에서 `isAdmin: false` 확인
   - 해결: 세션 쿠키 설정 확인, HTTPS 설정 확인

2. **DB 연결 문제**: 데이터베이스 연결 실패
   - 확인: error_log에서 `DB query error` 확인
   - 해결: DB 접속 정보 확인 (`.env` 파일)

3. **테이블 없음**: 필요한 테이블이 생성되지 않음
   - 확인: error_log에서 `Table creation error` 확인
   - 해결: `db_setup.php` 실행 또는 수동 테이블 생성

4. **권한 체크 실패**: 관리자 로그인 상태가 서버에서 인식되지 않음
   - 확인: error_log에서 세션 데이터 확인
   - 해결: 세션 쿠키 설정, 세션 저장 경로 확인

## 다음 단계

1. 배포 서버의 PHP error_log 확인
2. Network 탭에서 API 요청/응답 확인
3. 로그 메시지를 기반으로 문제 원인 파악
4. 필요한 경우 추가 디버깅 정보 요청
