<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>상품 테스트 | DewScent</title>
    <style>
        body {
            font-family: 'Noto Sans KR', sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background: #f5f5f5;
        }
        h1 {
            color: #5f7161;
            text-align: center;
        }
        .status {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .product {
            background: white;
            padding: 15px;
            margin: 10px 0;
            border-radius: 8px;
            border-left: 4px solid #5f7161;
        }
        .product-name {
            font-size: 18px;
            font-weight: bold;
            color: #333;
        }
        .product-price {
            color: #5f7161;
            font-size: 16px;
            margin-top: 5px;
        }
        button {
            background: linear-gradient(135deg, #5f7161 0%, #c96473 100%);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 50px;
            cursor: pointer;
            font-size: 16px;
            margin: 10px 5px;
        }
        button:hover {
            opacity: 0.9;
        }
        #result {
            margin-top: 20px;
        }
        .loading {
            color: #856404;
            font-style: italic;
        }
    </style>
</head>
<body>
    <h1>🧪 DewScent 상품 로딩 테스트</h1>

    <div class="status">
        <h2>진단 결과</h2>
        <div id="status">
            <p class="loading">⏳ 테스트 대기 중... 아래 버튼을 클릭하세요.</p>
        </div>
    </div>

    <div style="text-align: center;">
        <button onclick="testAPI()">API 테스트 실행</button>
        <button onclick="window.location.href='index.php'">메인 페이지로</button>
    </div>

    <div id="result"></div>

    <script>
        async function testAPI() {
            const statusDiv = document.getElementById('status');
            const resultDiv = document.getElementById('result');

            statusDiv.innerHTML = '<p class="loading">⏳ API 호출 중...</p>';
            resultDiv.innerHTML = '';

            try {
                // API 호출
                const response = await fetch('/api/products.php');

                // 상태 코드 확인
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }

                // Content-Type 확인
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    throw new Error(`응답이 JSON이 아닙니다. Content-Type: ${contentType}`);
                }

                // JSON 파싱
                const products = await response.json();

                // 결과 표시
                if (!Array.isArray(products)) {
                    throw new Error('응답이 배열 형식이 아닙니다.');
                }

                statusDiv.innerHTML = `
                    <p class="success">✅ API 호출 성공!</p>
                    <p><strong>상품 개수:</strong> ${products.length}개</p>
                    <p><strong>Content-Type:</strong> ${contentType}</p>
                `;

                if (products.length === 0) {
                    resultDiv.innerHTML = '<div class="status"><p class="error">⚠️ 상품이 0개입니다! DB에서 status가 "판매중"인 상품이 있는지 확인하세요.</p></div>';
                } else {
                    let html = '<div class="status"><h3>📦 상품 목록 (최대 10개)</h3>';
                    products.slice(0, 10).forEach(product => {
                        html += `
                            <div class="product">
                                <div class="product-name">${product.name || '이름 없음'}</div>
                                <div class="product-price">가격: ${(product.price || 0).toLocaleString()}원</div>
                                <div style="color: #666; margin-top: 5px;">
                                    타입: ${product.type || '-'} |
                                    재고: ${product.stock || 0} |
                                    상태: ${product.status || '-'}
                                </div>
                            </div>
                        `;
                    });
                    html += '</div>';
                    resultDiv.innerHTML = html;
                }

            } catch (error) {
                statusDiv.innerHTML = `
                    <p class="error">❌ 오류 발생!</p>
                    <p><strong>오류 메시지:</strong></p>
                    <pre style="background: #f8f9fa; padding: 10px; border-radius: 5px; overflow-x: auto;">${error.message}</pre>
                `;

                // 상세 디버그 정보
                resultDiv.innerHTML = `
                    <div class="status">
                        <h3>🔍 디버그 정보</h3>
                        <p><strong>오류 유형:</strong> ${error.name}</p>
                        <p><strong>API URL:</strong> /api/products.php</p>
                        <p><strong>브라우저:</strong> ${navigator.userAgent}</p>
                    </div>
                `;
            }
        }

        // 페이지 로드 시 자동 실행
        window.addEventListener('load', function() {
            console.log('페이지 로드 완료. 테스트 준비됨.');
        });
    </script>
</body>
</html>
