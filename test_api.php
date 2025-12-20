<?php
/**
 * API 응답 테스트 페이지
 * 각 API 엔드포인트가 정상 작동하는지 확인
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API 테스트 | DewScent</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Noto Sans KR', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 2rem;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        h1 { color: #5f7161; margin-bottom: 1.5rem; }
        h2 { color: #5f7161; margin: 1.5rem 0 1rem; font-size: 1.2rem; }
        .api-test {
            background: #f8f9fa;
            border-left: 4px solid #5f7161;
            padding: 1rem;
            margin: 1rem 0;
            border-radius: 8px;
        }
        .btn {
            display: inline-block;
            padding: 0.5rem 1.5rem;
            background: linear-gradient(135deg, #5f7161 0%, #c96473 100%);
            color: white;
            border: none;
            border-radius: 50px;
            cursor: pointer;
            font-size: 0.9rem;
            margin: 0.5rem 0.5rem 0.5rem 0;
        }
        .btn:hover { opacity: 0.9; }
        .result {
            background: #fff;
            border: 1px solid #dee2e6;
            padding: 1rem;
            margin-top: 1rem;
            border-radius: 8px;
            max-height: 400px;
            overflow-y: auto;
        }
        .success { color: #28a745; font-weight: 600; }
        .error { color: #dc3545; font-weight: 600; }
        .info { color: #17a2b8; }
        pre {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            overflow-x: auto;
            font-size: 0.85rem;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        .status {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
            margin-left: 0.5rem;
        }
        .status.ok { background: #d4edda; color: #155724; }
        .status.fail { background: #f8d7da; color: #721c24; }
        .loading { color: #856404; font-style: italic; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 API 엔드포인트 테스트</h1>
        <p style="margin-bottom: 2rem; color: #6c757d;">
            각 버튼을 클릭하여 API가 정상적으로 응답하는지 확인하세요.
        </p>

        <!-- 상품 API -->
        <div class="api-test">
            <h2>1. 상품 목록 API</h2>
            <p>엔드포인트: <code>api/products.php</code></p>
            <button class="btn" onclick="testAPI('api/products.php', 'products')">테스트 실행</button>
            <button class="btn" onclick="openAPI('api/products.php')">브라우저에서 열기</button>
            <div id="products-result" class="result" style="display:none;"></div>
        </div>

        <!-- 메인 상품 API -->
        <div class="api-test">
            <h2>2. 메인 상품 API</h2>
            <p>엔드포인트: <code>api/main-products.php</code></p>
            <button class="btn" onclick="testAPI('api/main-products.php', 'main-products')">테스트 실행</button>
            <button class="btn" onclick="openAPI('api/main-products.php')">브라우저에서 열기</button>
            <div id="main-products-result" class="result" style="display:none;"></div>
        </div>

        <!-- 배너 API -->
        <div class="api-test">
            <h2>3. 배너 API</h2>
            <p>엔드포인트: <code>api/banners.php</code></p>
            <button class="btn" onclick="testAPI('api/banners.php', 'banners')">테스트 실행</button>
            <button class="btn" onclick="openAPI('api/banners.php')">브라우저에서 열기</button>
            <div id="banners-result" class="result" style="display:none;"></div>
        </div>

        <!-- 감정 API -->
        <div class="api-test">
            <h2>4. 감정 API</h2>
            <p>엔드포인트: <code>api/emotions.php</code></p>
            <button class="btn" onclick="testAPI('api/emotions.php', 'emotions')">테스트 실행</button>
            <button class="btn" onclick="openAPI('api/emotions.php')">브라우저에서 열기</button>
            <div id="emotions-result" class="result" style="display:none;"></div>
        </div>

        <!-- 섹션 설정 API -->
        <div class="api-test">
            <h2>5. 섹션 설정 API</h2>
            <p>엔드포인트: <code>api/sections.php</code></p>
            <button class="btn" onclick="testAPI('api/sections.php', 'sections')">테스트 실행</button>
            <button class="btn" onclick="openAPI('api/sections.php')">브라우저에서 열기</button>
            <div id="sections-result" class="result" style="display:none;"></div>
        </div>

        <div style="margin-top: 2rem; padding-top: 2rem; border-top: 2px solid #dee2e6;">
            <h2>📋 진단 결과 요약</h2>
            <div id="summary" style="margin-top: 1rem;">
                <p class="info">위의 버튼들을 클릭하여 API를 테스트하세요.</p>
            </div>
        </div>

        <div style="text-align: center; margin-top: 2rem;">
            <a href="index.php" class="btn">메인 페이지로 이동</a>
            <button class="btn" onclick="testAll()">모든 API 한번에 테스트</button>
        </div>
    </div>

    <script>
        const results = {};

        function testAPI(endpoint, name) {
            const resultDiv = document.getElementById(name + '-result');
            resultDiv.style.display = 'block';
            resultDiv.innerHTML = '<p class="loading">⏳ API 호출 중...</p>';

            fetch(endpoint)
                .then(response => {
                    const contentType = response.headers.get('content-type');
                    const statusCode = response.status;

                    if (!response.ok) {
                        throw new Error(`HTTP ${statusCode}: ${response.statusText}`);
                    }

                    if (!contentType || !contentType.includes('application/json')) {
                        throw new Error('응답이 JSON 형식이 아닙니다. Content-Type: ' + contentType);
                    }

                    return response.json();
                })
                .then(data => {
                    results[name] = { success: true, data: data };

                    let html = '<p class="success">✅ API 호출 성공!</p>';
                    html += '<p><strong>응답 데이터:</strong></p>';

                    if (Array.isArray(data)) {
                        html += `<p class="info">📦 배열 길이: ${data.length}개</p>`;
                        if (data.length > 0) {
                            html += '<p><strong>첫 번째 항목:</strong></p>';
                            html += '<pre>' + JSON.stringify(data[0], null, 2) + '</pre>';
                            if (data.length > 1) {
                                html += `<p class="info">... 외 ${data.length - 1}개 항목</p>`;
                            }
                        } else {
                            html += '<p class="error">⚠️ 배열이 비어있습니다!</p>';
                        }
                    } else {
                        html += '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
                    }

                    resultDiv.innerHTML = html;
                    updateSummary();
                })
                .catch(error => {
                    results[name] = { success: false, error: error.message };

                    let html = '<p class="error">❌ API 호출 실패!</p>';
                    html += '<p><strong>오류 메시지:</strong></p>';
                    html += '<pre>' + error.message + '</pre>';
                    html += '<p class="info">💡 브라우저에서 직접 열어서 상세 오류를 확인하세요.</p>';

                    resultDiv.innerHTML = html;
                    updateSummary();
                });
        }

        function openAPI(endpoint) {
            window.open(endpoint, '_blank');
        }

        function testAll() {
            testAPI('api/products.php', 'products');
            setTimeout(() => testAPI('api/main-products.php', 'main-products'), 500);
            setTimeout(() => testAPI('api/banners.php', 'banners'), 1000);
            setTimeout(() => testAPI('api/emotions.php', 'emotions'), 1500);
            setTimeout(() => testAPI('api/sections.php', 'sections'), 2000);
        }

        function updateSummary() {
            const summaryDiv = document.getElementById('summary');
            const total = Object.keys(results).length;
            const success = Object.values(results).filter(r => r.success).length;
            const failed = total - success;

            let html = `<p><strong>테스트 완료:</strong> ${total}개 API</p>`;
            html += `<p class="success">✅ 성공: ${success}개</p>`;
            if (failed > 0) {
                html += `<p class="error">❌ 실패: ${failed}개</p>`;
            }

            // 실패한 API가 있으면 상세 정보 표시
            if (failed > 0) {
                html += '<div style="margin-top: 1rem; padding: 1rem; background: #fff3cd; border-radius: 8px;">';
                html += '<p style="font-weight: 600; color: #856404;">⚠️ 실패한 API:</p>';
                html += '<ul style="margin-left: 2rem; margin-top: 0.5rem;">';
                for (const [name, result] of Object.entries(results)) {
                    if (!result.success) {
                        html += `<li><code>${name}</code>: ${result.error}</li>`;
                    }
                }
                html += '</ul>';
                html += '</div>';
            }

            summaryDiv.innerHTML = html;
        }
    </script>
</body>
</html>
