# 🚀 닷홈 호스팅 배포 가이드

DewScent를 닷홈 호스팅에 배포하는 완전한 가이드입니다.

---

## 📋 배포 전 준비사항

### 1. 닷홈 호스팅 계정 확인

닷홈에 로그인하여 다음 정보를 확인하세요:
- **호스팅 ID**: 예) `your_id`
- **FTP 비밀번호**
- **MySQL 데이터베이스 이름**: `your_id_dewscent` 형식
- **MySQL 비밀번호**

### 2. 파일 업로드 (FTP)

FileZilla 등의 FTP 클라이언트로 접속:
```
호스트: ftp.dothome.co.kr
사용자명: your_id
비밀번호: [FTP 비밀번호]
포트: 21
```

**중요**: 모든 파일을 `html` 폴더에 업로드하세요!

---

## ⚙️ 배포 후 필수 설정

### 1단계: .env 파일 생성

FTP로 접속하여 `html/.env` 파일을 생성하고 다음 내용을 입력하세요:

```bash
# 환경 설정
APP_ENV=production

# 사이트 URL (닷홈 무료: http, 유료: https 가능)
SITE_URL=http://your_id.dothome.co.kr

# 데이터베이스 설정 (닷홈 MySQL)
DB_HOST=localhost
DB_USER=your_id
DB_PASS=mysql_password
DB_NAME=your_id_dewscent

# 카카오 소셜 로그인 (선택사항 - 없어도 기본 기능 작동)
KAKAO_CLIENT_ID=

# 네이버 소셜 로그인 (선택사항 - 없어도 기본 기능 작동)
NAVER_CLIENT_ID=
NAVER_CLIENT_SECRET=

# 토스페이먼츠 (선택사항 - 결제 기능 사용 시)
TOSS_CLIENT_KEY=
TOSS_SECRET_KEY=
```

**중요 사항:**
- `your_id`를 실제 닷홈 ID로 변경하세요
- `mysql_password`를 실제 MySQL 비밀번호로 변경하세요
- 닷홈 무료 호스팅은 `http://`만 지원 (SSL 미지원)
- 소셜 로그인 키가 없어도 사이트는 작동합니다 (일반 회원가입/로그인만 사용)

### 2단계: MySQL 데이터베이스 생성

닷홈 관리자 페이지에서:
1. **호스팅 관리** → **MySQL 관리**
2. **데이터베이스 추가** 클릭
3. 데이터베이스 이름: `dewscent` (자동으로 `your_id_dewscent`가 됨)
4. **생성** 클릭

### 3단계: 초기화 스크립트 실행

브라우저에서 다음 URL에 접속:
```
http://your_id.dothome.co.kr/init_deploy.php
```

이 스크립트가 자동으로:
- ✅ 모든 테이블 생성
- ✅ 기본 데이터 삽입 (상품, 배너 등)
- ✅ 관리자 계정 생성
- ✅ 데이터베이스 상태 확인

**성공 메시지가 나오면 완료!**

---

## 🔑 기본 관리자 계정

```
이메일: admin@dewscent.com
비밀번호: admin123
```

**⚠️ 중요**: 로그인 후 즉시 비밀번호를 변경하세요!

관리자 페이지: `http://your_id.dothome.co.kr/admin/login.php`

---

## 🔧 닷홈 특화 문제 해결

### 문제 1: "DB 연결 실패" 에러

**원인**: .env 파일 설정 오류

**해결방법**:
1. .env 파일이 `html` 폴더에 있는지 확인
2. DB_NAME이 `your_id_dewscent` 형식인지 확인
3. MySQL 비밀번호가 정확한지 확인

### 문제 2: "500 Internal Server Error"

**원인**: PHP 문법 에러 또는 권한 문제

**해결방법**:
1. 파일 권한 확인:
   ```
   모든 .php 파일: 644
   폴더: 755
   ```
2. 닷홈 관리자 페이지에서 PHP 버전 확인 (7.4 이상 권장)

### 문제 3: "Headers already sent" 에러

**원인**: 파일 인코딩 문제 (BOM)

**해결방법**:
- 모든 PHP 파일을 **UTF-8 without BOM**으로 저장
- 메모장 대신 VSCode, Notepad++ 사용

### 문제 4: 이미지가 표시되지 않음

**원인**: 이미지 폴더가 없음

**해결방법**:
FTP로 다음 폴더 생성:
```
html/public/images/products/
html/public/images/banners/
html/public/images/popups/
html/uploads/images/
```

폴더 권한: 755

### 문제 5: CSS/JS 파일이 로드되지 않음

**원인**: 경로 문제

**해결방법**:
- 브라우저 개발자 도구(F12) → Console 확인
- 404 에러가 나는 파일의 경로 확인
- .env의 SITE_URL이 정확한지 확인

---

## 📱 소셜 로그인 설정 (선택사항)

소셜 로그인을 사용하려면:

### 카카오 로그인

1. [카카오 개발자 센터](https://developers.kakao.com/) 접속
2. 애플리케이션 추가
3. **플랫폼 설정** → **Web** → 사이트 도메인 등록
   ```
   http://your_id.dothome.co.kr
   ```
4. **Redirect URI** 등록:
   ```
   http://your_id.dothome.co.kr/api/kakao_callback.php
   ```
5. **REST API 키** 복사 → .env의 `KAKAO_CLIENT_ID`에 입력

### 네이버 로그인

1. [네이버 개발자 센터](https://developers.naver.com/) 접속
2. 애플리케이션 등록
3. **서비스 URL** 입력:
   ```
   http://your_id.dothome.co.kr
   ```
4. **Callback URL** 입력:
   ```
   http://your_id.dothome.co.kr/api/naver_callback.php
   ```
5. **Client ID**와 **Client Secret** 복사 → .env에 입력

---

## 🔒 보안 권장사항

배포 완료 후 다음 파일들을 삭제하세요:

```bash
init_deploy.php
check_*.php
debug_*.php
create_*.php
setup_database.php
insert_default_data.php
```

FTP에서 직접 삭제하거나, 관리자 페이지에서 파일 관리 기능 사용

---

## ✅ 배포 체크리스트

- [ ] FTP로 모든 파일 업로드 (`html` 폴더에)
- [ ] .env 파일 생성 및 설정
- [ ] MySQL 데이터베이스 생성
- [ ] init_deploy.php 실행
- [ ] 관리자 로그인 확인
- [ ] 관리자 비밀번호 변경
- [ ] 메인 페이지 정상 작동 확인
- [ ] (선택) 소셜 로그인 설정
- [ ] 보안 파일 삭제 (init_deploy.php 등)

---

## 🆘 추가 도움이 필요하신가요?

### 닷홈 고객센터
- 전화: 1588-2120
- 이메일: help@dothome.co.kr

### 일반적인 에러

**"Class 'PDO' not found"**
→ 닷홈 관리자 페이지에서 PHP 버전을 7.4 이상으로 변경

**"Permission denied"**
→ FTP에서 파일 권한을 644, 폴더 권한을 755로 변경

**"Too many connections"**
→ 닷홈 무료 호스팅은 동시 접속 제한이 있습니다. 유료로 업그레이드 필요

---

## 🎉 배포 완료!

축하합니다! DewScent가 닷홈에서 정상적으로 작동합니다.

메인 페이지: `http://your_id.dothome.co.kr`
관리자 페이지: `http://your_id.dothome.co.kr/admin/login.php`
