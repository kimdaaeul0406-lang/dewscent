(function (w) {
  const USE_MOCK_API = false; // false로 두면 실제 API 엔드포인트로 연결
  const BASE_URL = (() => {
    const root = (w.DS_BASE_URL || "").replace(/\/$/, "");
    return root ? `${root}/api` : "/api";
  })();
  const PRODUCTS_KEY = "dewscent_admin_products"; // LocalStorage 키

  function delay(ms) {
    return new Promise((resolve) => setTimeout(resolve, ms));
  }

  // 기본 상품 데이터 (초기 seed)
  const defaultProducts = [
    {
      id: 1,
      name: "새벽이슬",
      price: 42000,
      desc: "맑고 투명한 아침이슬의 향기",
      category: "향수",
      badge: "BEST",
      rating: 4.8,
      reviews: 128,
      stock: 50,
      status: "판매중",
      imageUrl: "",
      createdAt: "2025-01-01",
    },
    {
      id: 2,
      name: "포근한 오후",
      price: 38000,
      desc: "따뜻하고 포근한 우디 머스크",
      category: "향수",
      badge: "NEW",
      rating: 4.6,
      reviews: 89,
      stock: 35,
      status: "판매중",
      imageUrl: "",
      createdAt: "2025-01-05",
    },
    {
      id: 3,
      name: "벚꽃 산책",
      price: 45000,
      desc: "봄날의 벚꽃길을 걷는 듯한 향",
      category: "향수",
      badge: "",
      rating: 4.7,
      reviews: 156,
      stock: 20,
      status: "판매중",
      imageUrl: "",
      createdAt: "2025-01-10",
    },
    {
      id: 4,
      name: "비 온 뒤 숲",
      price: 48000,
      desc: "비 온 뒤 숲속의 청량한 공기",
      category: "향수",
      badge: "",
      rating: 4.5,
      reviews: 72,
      stock: 45,
      status: "판매중",
      imageUrl: "",
      createdAt: "2025-01-15",
    },
    {
      id: 5,
      name: "달빛 정원",
      price: 52000,
      desc: "밤에 피어나는 은은한 꽃향기",
      category: "향수",
      badge: "BEST",
      rating: 4.9,
      reviews: 203,
      stock: 15,
      status: "판매중",
      imageUrl: "",
      createdAt: "2025-01-20",
    },
    {
      id: 6,
      name: "코튼 브리즈",
      price: 35000,
      desc: "갓 빨래한 이불 같은 포근함",
      category: "바디미스트",
      badge: "",
      rating: 4.4,
      reviews: 95,
      stock: 60,
      status: "판매중",
      imageUrl: "",
      createdAt: "2025-02-01",
    },
    {
      id: 7,
      name: "레몬 버베나",
      price: 28000,
      desc: "상큼한 레몬과 허브의 조화",
      category: "바디미스트",
      badge: "NEW",
      rating: 4.3,
      reviews: 67,
      stock: 40,
      status: "판매중",
      imageUrl: "",
      createdAt: "2025-02-10",
    },
    {
      id: 8,
      name: "피오니 가든",
      price: 32000,
      desc: "화사한 작약 꽃다발의 향기",
      category: "디퓨저",
      badge: "",
      rating: 4.6,
      reviews: 112,
      stock: 25,
      status: "판매중",
      imageUrl: "",
      createdAt: "2025-02-15",
    },
  ];

  // LocalStorage에서 상품 가져오기 (없으면 기본값 저장)
  function getStoredProducts() {
    try {
      const stored = localStorage.getItem(PRODUCTS_KEY);
      if (stored) return JSON.parse(stored);
      // 최초 실행 시 기본 상품 저장
      localStorage.setItem(PRODUCTS_KEY, JSON.stringify(defaultProducts));
      return defaultProducts;
    } catch (e) {
      return defaultProducts;
    }
  }

  // LocalStorage에 상품 저장
  function setStoredProducts(products) {
    localStorage.setItem(PRODUCTS_KEY, JSON.stringify(products));
  }

  // 샘플 목업 데이터
  const mock = {
    profile: {
      id: 1,
      name: "홍길동",
      email: "hong@example.com",
      joinedAt: "2025-01-01",
      addresses: [
        {
          id: 1,
          label: "기본",
          recipient: "홍길동",
          address: "서울 강남구 테헤란로 123",
          phone: "010-1234-5678",
        },
      ],
    },
    orders: [
      {
        id: "ORD-2025001",
        total: 32000,
        status: "결제완료",
        orderedAt: "2025-01-15",
      },
      {
        id: "ORD-2025002",
        total: 87000,
        status: "배송중",
        orderedAt: "2025-02-02",
      },
    ],
    users: [
      {
        id: 1,
        name: "홍길동",
        email: "hong@example.com",
        joinedAt: "2025-01-01",
        status: "active",
      },
      {
        id: 2,
        name: "김영희",
        email: "kim@example.com",
        joinedAt: "2025-02-10",
        status: "active",
      },
    ],
    adminOrders: [
      {
        id: "ORD-2025001",
        customer: "홍길동",
        total: 32000,
        status: "결제완료",
        orderedAt: "2025-01-15",
      },
      {
        id: "ORD-2025002",
        customer: "김영희",
        total: 87000,
        status: "배송중",
        orderedAt: "2025-02-02",
      },
    ],
  };

  async function getJSON(path, opts = {}) {
    // BASE_URL이 이미 /api를 포함하므로, path에서 /api 제거
    // 하지만 path가 /api/로 시작하면 제거, 아니면 그대로 사용
    if (path.startsWith("/api/")) {
      path = path.substring(5); // '/api/' 제거 (인덱스 5부터)
    } else if (path.startsWith("/api")) {
      path = path.substring(4); // '/api' 제거 (인덱스 4부터)
    }
    // path가 /로 시작하지 않으면 / 추가
    if (!path.startsWith("/")) {
      path = "/" + path;
    }
    // BASE_URL과 path를 합쳐서 최종 URL 생성
    const finalUrl = `${BASE_URL}${path}`;

    // HTTPS 강제 (Mixed Content 방지)
    const secureUrl = finalUrl.replace(/^http:\/\//, 'https://');

    // 타임아웃 설정 (10초)
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), 10000);

    try {
      const res = await fetch(secureUrl, {
        credentials: "include",
        headers: { "Content-Type": "application/json" },
        signal: controller.signal,
        ...opts,
      });
      clearTimeout(timeoutId);
      
      // HTTP 에러 상태 코드 처리
      if (!res.ok) {
        let errorData;
        try {
          errorData = await res.json();
        } catch (e) {
          errorData = { error: `HTTP ${res.status}: ${res.statusText}`, message: res.statusText };
        }
        const error = new Error(errorData.message || errorData.error || `HTTP ${res.status}`);
        error.status = res.status;
        error.data = errorData;
        throw error;
      }
      
      if (!res.ok) {
        let errorMessage = `API Error: ${res.status}`;
        try {
          const errorData = await res.json();
          if (errorData.message) {
            errorMessage = errorData.message;
          }
        } catch (e) {
          // JSON 파싱 실패 시 기본 메시지 사용
        }
        const error = new Error(errorMessage);
        error.status = res.status;
        throw error;
      }
      return await res.json();
    } catch (err) {
      clearTimeout(timeoutId);
      if (err.name === 'AbortError') {
        throw new Error('요청 시간 초과 (10초)');
      }
      throw err;
    }
  }

  async function postJSON(path, data, opts = {}) {
    // BASE_URL이 이미 /api를 포함하므로, path에서 /api 제거
    if (path.startsWith("/api/")) {
      path = path.substring(5);
    } else if (path.startsWith("/api")) {
      path = path.substring(4);
    }
    // path가 /로 시작하지 않으면 / 추가
    if (!path.startsWith("/")) {
      path = "/" + path;
    }
    // BASE_URL과 path를 합쳐서 최종 URL 생성
    const finalUrl = `${BASE_URL}${path}`;
    
    // HTTPS 강제 (Mixed Content 방지)
    const secureUrl = finalUrl.replace(/^http:\/\//, 'https://');
    
    // 타임아웃 설정 (10초)
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), 10000);
    
    try {
      const res = await fetch(secureUrl, {
        method: "POST",
        credentials: "include",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(data),
        signal: controller.signal,
        ...opts,
      });
      clearTimeout(timeoutId);
      
      if (!res.ok) {
        let errorMessage = `API Error: ${res.status}`;
        try {
          const errorData = await res.json();
          if (errorData.message) {
            errorMessage = errorData.message;
          }
        } catch (e) {
          // JSON 파싱 실패 시 기본 메시지 사용
        }
        const error = new Error(errorMessage);
        error.status = res.status;
        throw error;
      }
      return await res.json();
    } catch (err) {
      clearTimeout(timeoutId);
      if (err.name === 'AbortError') {
        throw new Error('요청 시간 초과 (10초)');
      }
      throw err;
    }
  }

  async function putJSON(path, data, opts = {}) {
    // BASE_URL이 이미 /api를 포함하므로, path에서 /api 제거
    if (path.startsWith("/api/")) {
      path = path.substring(5);
    } else if (path.startsWith("/api")) {
      path = path.substring(4);
    }
    // path가 /로 시작하지 않으면 / 추가
    if (!path.startsWith("/")) {
      path = "/" + path;
    }
    // BASE_URL과 path를 합쳐서 최종 URL 생성
    const finalUrl = `${BASE_URL}${path}`;
    
    // HTTPS 강제
    const secureUrl = finalUrl.replace(/^http:\/\//, 'https://');
    
    // 타임아웃 설정
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), 10000);
    
    try {
      const res = await fetch(secureUrl, {
        method: "PUT",
        credentials: "include",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(data),
        signal: controller.signal,
        ...opts,
      });
      clearTimeout(timeoutId);
      if (!res.ok) {
        let errorMessage = `API Error: ${res.status}`;
        try {
          const errorData = await res.json();
          if (errorData.message) {
            errorMessage = errorData.message;
          }
        } catch (e) {
          // JSON 파싱 실패 시 기본 메시지 사용
        }
        const error = new Error(errorMessage);
        error.status = res.status;
        throw error;
      }
      return await res.json();
    } catch (err) {
      clearTimeout(timeoutId);
      if (err.name === 'AbortError') {
        throw new Error('요청 시간 초과 (10초)');
      }
      throw err;
    }
  }

  async function patchJSON(path, data, opts = {}) {
    // BASE_URL이 이미 /api를 포함하므로, path에서 /api 제거
    if (path.startsWith("/api/")) {
      path = path.substring(5);
    } else if (path.startsWith("/api")) {
      path = path.substring(4);
    }
    // path가 /로 시작하지 않으면 / 추가
    if (!path.startsWith("/")) {
      path = "/" + path;
    }
    // BASE_URL과 path를 합쳐서 최종 URL 생성
    const finalUrl = `${BASE_URL}${path}`;
    
    // HTTPS 강제
    const secureUrl = finalUrl.replace(/^http:\/\//, 'https://');
    
    // 타임아웃 설정
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), 10000);
    
    try {
      const res = await fetch(secureUrl, {
        method: "PATCH",
        credentials: "include",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(data),
        signal: controller.signal,
        ...opts,
      });
      clearTimeout(timeoutId);

      // 응답 본문 확인
      const text = await res.text();
      let jsonData;

      try {
        jsonData = text ? JSON.parse(text) : {};
      } catch (e) {
        console.error("[API] JSON 파싱 실패:", text, e);
        const error = new Error(
          "서버 응답 오류가 발생했습니다. 잠시 후 다시 시도해주세요."
        );
        error.status = res.status;
        throw error;
      }

      if (!res.ok) {
        let errorMessage = jsonData.message || `API Error: ${res.status}`;
        const error = new Error(errorMessage);
        error.status = res.status;
        throw error;
      }

      return jsonData;
    } catch (err) {
      clearTimeout(timeoutId);
      if (err.name === 'AbortError') {
        throw new Error('요청 시간 초과 (10초)');
      }
      throw err;
    }
  } catch (err) {
      clearTimeout(timeoutId);
      if (err.name === 'AbortError') {
        throw new Error('요청 시간 초과 (10초)');
      }
      throw err;
    }
  }

  // 공개 API (목업/실API 스위치)
  async function getProfile() {
    if (USE_MOCK_API) {
      await delay(200);
      return mock.profile;
    }
    return await getJSON("/me");
  }

  async function getOrders(opts) {
    if (USE_MOCK_API) {
      await delay(200);
      const from = opts?.from ? new Date(opts.from) : null;
      const to = opts?.to ? new Date(opts.to) : null;
      if (!from && !to) return mock.orders;
      return (mock.orders || []).filter((o) => {
        const d = new Date(o.orderedAt);
        if (from && d < from) return false;
        if (to) {
          const toEnd = new Date(to);
          toEnd.setHours(23, 59, 59, 999);
          if (d > toEnd) return false;
        }
        return true;
      });
    }
    const params = new URLSearchParams();
    if (opts?.from) params.set("from", opts.from);
    if (opts?.to) params.set("to", opts.to);
    const qs = params.toString();
    return await getJSON(`/orders.php${qs ? "?" + qs : ""}`);
  }

  async function getUsers() {
    if (USE_MOCK_API) {
      await delay(200);
      return mock.users;
    }
    return await getJSON("/admin/users.php");
  }

  // ========== 문의 API ==========
  async function getInquiries() {
    if (USE_MOCK_API) {
      await delay(200);
      return mock.inquiries || [];
    }
    return await getJSON("/inquiries.php");
  }

  async function createInquiry(data) {
    if (USE_MOCK_API) {
      await delay(200);
      const newInquiry = {
        id: Date.now(),
        ...data,
        status: "waiting",
        createdAt: new Date().toISOString().split("T")[0],
      };
      return { ok: true, inquiry: newInquiry };
    }
    return await getJSON("/inquiries.php", {
      method: "POST",
      body: JSON.stringify(data),
    });
  }

  async function updateInquiryAnswer(id, answer) {
    if (USE_MOCK_API) {
      await delay(200);
      return { ok: true };
    }
    return await getJSON("/inquiries.php", {
      method: "PUT",
      body: JSON.stringify({ id, answer }),
    });
  }

  // ========== 리뷰 API ==========
  async function getReviews(productId) {
    if (USE_MOCK_API) {
      await delay(200);
      return mock.reviews?.[productId] || [];
    }
    // productId가 null이면 모든 리뷰 조회 (관리자용)
    const url = productId
      ? `/reviews.php?product_id=${productId}`
      : `/reviews.php`;
    return await getJSON(url);
  }

  async function createReview(productId, data) {
    if (USE_MOCK_API) {
      await delay(200);
      const newReview = {
        id: Date.now(),
        productId,
        ...data,
        date: new Date().toISOString().split("T")[0],
      };
      return { ok: true, review: newReview };
    }
    return await getJSON(`/reviews.php?product_id=${productId}`, {
      method: "POST",
      body: JSON.stringify(data),
    });
  }

  async function deleteReview(productId, reviewId = null) {
    if (USE_MOCK_API) {
      await delay(200);
      return { ok: true };
    }
    // reviewId가 있으면 특정 리뷰 삭제, 없으면 해당 상품의 모든 리뷰 삭제
    const url = reviewId
      ? `/reviews.php?product_id=${productId}&review_id=${reviewId}`
      : `/reviews.php?product_id=${productId}`;
    return await getJSON(url, {
      method: "DELETE",
    });
  }

  async function getAdminOrders() {
    if (USE_MOCK_API) {
      await delay(200);
      return mock.adminOrders;
    }
    return await getJSON("/orders.php");
  }

  async function createOrder(orderData) {
    if (USE_MOCK_API) {
      await delay(300);
      return {
        ok: true,
        orderId: Date.now(),
        orderNumber: orderData.id || orderData.orderNumber,
      };
    }
    return await postJSON("/orders.php", orderData);
  }

  async function updateOrderStatus(orderNumber, status) {
    if (USE_MOCK_API) {
      await delay(200);
      return { ok: true, message: "주문 상태가 변경되었습니다." };
    }
    return await putJSON("/orders.php", { orderNumber, status });
  }

  async function requestOrderCancel(orderNumber, reason = "") {
    if (USE_MOCK_API) {
      await delay(200);
      return { ok: true, message: "취소 요청이 접수되었습니다." };
    }
    return await patchJSON("/orders.php", {
      orderNumber,
      action: "cancel_request",
      reason,
    });
  }

  async function confirmPayment(orderNumber) {
    if (USE_MOCK_API) {
      await delay(200);
      return { ok: true, message: "결제가 확인되었습니다." };
    }
    return await patchJSON("/orders.php", {
      orderNumber,
      action: "confirm_payment",
    });
  }

  async function approveCancel(orderNumber) {
    if (USE_MOCK_API) {
      await delay(200);
      return { ok: true, message: "주문 취소가 승인되었습니다." };
    }
    return await patchJSON("/orders.php", {
      orderNumber,
      action: "approve_cancel",
    });
  }

  async function rejectCancel(orderNumber) {
    if (USE_MOCK_API) {
      await delay(200);
      return { ok: true, message: "취소 요청이 거부되었습니다." };
    }
    return await patchJSON("/orders.php", {
      orderNumber,
      action: "reject_cancel",
    });
  }

  // ========== 상품 CRUD ==========

  // 상품 목록 조회
  async function getProducts() {
    if (USE_MOCK_API) {
      await delay(100);
      return getStoredProducts();
    }
    return await getJSON("/products.php");
  }

  // 상품 단일 조회
  async function getProduct(id) {
    if (USE_MOCK_API) {
      await delay(50);
      const products = getStoredProducts();
      return products.find((p) => p.id === id) || null;
    }
    return await getJSON(`/products.php/${id}`);
  }

  // 상품 등록
  async function createProduct(data) {
    if (USE_MOCK_API) {
      await delay(100);
      const products = getStoredProducts();
      const newId =
        products.length > 0 ? Math.max(...products.map((p) => p.id)) + 1 : 1;
      const newProduct = {
        id: newId,
        name: data.name || "",
        price: parseInt(data.price) || 0,
        desc: data.desc || "",
        category: data.category || "향수",
        badge: data.badge || "",
        rating: parseFloat(data.rating) || 0,
        reviews: parseInt(data.reviews) || 0,
        stock: parseInt(data.stock) || 0,
        status: data.status || "판매중",
        imageUrl: data.imageUrl || "",
        createdAt: new Date().toISOString().split("T")[0],
      };
      products.unshift(newProduct); // 맨 앞에 추가
      setStoredProducts(products);
      return newProduct;
    }
    return await getJSON("/products.php", {
      method: "POST",
      body: JSON.stringify(data),
    });
  }

  // 상품 수정
  async function updateProduct(id, data) {
    if (USE_MOCK_API) {
      await delay(100);
      const products = getStoredProducts();
      const idx = products.findIndex((p) => p.id === id);
      if (idx === -1) throw new Error("상품을 찾을 수 없습니다.");
      products[idx] = { ...products[idx], ...data, id }; // id는 변경 불가
      setStoredProducts(products);
      return products[idx];
    }
    return await getJSON(`/products.php/${id}`, {
      method: "PUT",
      body: JSON.stringify(data),
    });
  }

  // 상품 삭제
  async function deleteProduct(id) {
    if (USE_MOCK_API) {
      await delay(100);
      const products = getStoredProducts();
      const filtered = products.filter((p) => p.id !== id);
      setStoredProducts(filtered);
      return { success: true };
    }
    return await getJSON(`/products.php/${id}`, {
      method: "DELETE",
    });
  }

  // 프론트엔드용 상품 목록 (판매중인 것만, 페이징 지원)
  async function getPublicProducts(options = {}) {
    if (USE_MOCK_API) {
      await delay(50);
      const products = getStoredProducts().filter((p) => p.status === "판매중");
      // 페이징 적용
      if (options.limit) {
        const offset = options.offset || 0;
        return products.slice(offset, offset + options.limit);
      }
      return products;
    }
    // 페이징 파라미터 추가
    const params = new URLSearchParams();
    if (options.limit) params.append('limit', options.limit);
    if (options.offset) params.append('offset', options.offset);
    const queryString = params.toString();
    return await getJSON(`/products.php${queryString ? '?' + queryString : ''}`);
  }

  // ========== 배너/캐러셀 관리 ==========
  let bannersCache = null;
  let bannersCacheTime = 0;
  const BANNERS_CACHE_DURATION = 60000; // 1분 캐시
  
  const defaultBanners = [
    {
      id: 1,
      title: "나에게 맞는 향기 찾기",
      subtitle: "3분 향기 테스트로 나만의 향을 발견하세요",
      link: "#fragrance-test",
      imageUrl: "",
      order: 1,
      active: true,
    },
    {
      id: 2,
      title: "새로운 향기의 시작",
      subtitle: "DewScent 2025 컬렉션",
      link: "pages/products.php",
      imageUrl: "",
      order: 2,
      active: true,
    },
    {
      id: 3,
      title: "봄의 향기를 담다",
      subtitle: "벚꽃 에디션 출시",
      link: "pages/products.php",
      imageUrl: "",
      order: 3,
      active: true,
    },
    {
      id: 4,
      title: "특별한 선물",
      subtitle: "기프트 세트 20% 할인",
      link: "pages/products.php",
      imageUrl: "",
      order: 4,
      active: true,
    },
    {
      id: 5,
      title: "시그니처 향기",
      subtitle: "베스트셀러 모음",
      link: "pages/products.php",
      imageUrl: "",
      order: 5,
      active: true,
    },
  ];

  async function getBanners() {
    try {
      const now = Date.now();
      if (bannersCache && now - bannersCacheTime < BANNERS_CACHE_DURATION) {
        return bannersCache;
      }
      
      const banners = await getJSON("/banners.php");
      bannersCache = banners || [];
      bannersCacheTime = now;
      return bannersCache;
    } catch (e) {
      console.error("배너 조회 오류:", e);
      return [];
    }
  }
  
  async function setBanners(banners) {
    try {
      // 개별 배너를 POST/PUT으로 저장
      for (const banner of banners) {
        if (banner.id) {
          await putJSON("/banners.php", banner);
        } else {
          await postJSON("/banners.php", banner);
        }
      }
      bannersCache = banners;
      bannersCacheTime = Date.now();
    } catch (e) {
      console.error("배너 저장 오류:", e);
      throw e;
    }
  }
  
  async function getActiveBanners() {
    try {
      const banners = await getBanners();
      if (!banners || !Array.isArray(banners)) {
        return [];
      }
      const activeBanners = banners.filter((b) => b.active !== false && b.active !== 0);
      return activeBanners.sort((a, b) => (a.order || 0) - (b.order || 0));
    } catch (e) {
      console.error("활성 배너 조회 오류:", e);
      return [];
    }
  }
  
  function clearBannersCache() {
    bannersCache = null;
    bannersCacheTime = 0;
  }

  // ========== 팝업 관리 ==========
  let popupsCache = null;
  let popupsCacheTime = 0;
  const POPUPS_CACHE_DURATION = 60000; // 1분 캐시

  async function getPopups() {
    try {
      const now = Date.now();
      if (popupsCache && now - popupsCacheTime < POPUPS_CACHE_DURATION) {
        return popupsCache;
      }
      
      const popups = await getJSON("/popups.php");
      popupsCache = popups || [];
      popupsCacheTime = now;
      return popupsCache;
    } catch (e) {
      console.error("팝업 조회 오류:", e);
      return [];
    }
  }
  
  async function setPopups(popups) {
    try {
      await postJSON("/popups.php", popups);
      popupsCache = popups;
      popupsCacheTime = Date.now();
    } catch (e) {
      console.error("팝업 저장 오류:", e);
      throw e;
    }
  }
  
  async function getActivePopups() {
    try {
      const popups = await getPopups();
      const now = new Date().toISOString().split("T")[0];
      return popups
        .filter((p) => {
          if (!p.active && p.active !== 0) return false;
          if (p.startDate && p.startDate > now) return false;
          if (p.endDate && p.endDate < now) return false;
          return true;
        })
        .sort((a, b) => (a.order || 0) - (b.order || 0));
    } catch (e) {
      console.error("활성 팝업 조회 오류:", e);
      return [];
    }
  }

  // ========== 메인 상품 배치 관리 ==========
  let mainProductsCache = null;
  let mainProductsCacheTime = 0;
  const MAIN_PRODUCTS_CACHE_DURATION = 60000; // 1분 캐시

  async function getMainProductIds() {
    try {
      const now = Date.now();
      if (mainProductsCache && now - mainProductsCacheTime < MAIN_PRODUCTS_CACHE_DURATION) {
        return mainProductsCache;
      }
      
      const ids = await getJSON("/main-products.php");
      mainProductsCache = ids || [];
      mainProductsCacheTime = now;
      return mainProductsCache;
    } catch (e) {
      console.error("메인 상품 조회 오류:", e);
      return [];
    }
  }
  
  async function setMainProductIds(ids) {
    try {
      await putJSON("/main-products.php", { productIds: ids });
      mainProductsCache = ids;
      mainProductsCacheTime = Date.now();
    } catch (e) {
      console.error("메인 상품 저장 오류:", e);
      throw e;
    }
  }

  // ========== 쿠폰 관리 ==========
  let couponsCache = null;
  let couponsCacheTime = 0;
  const CACHE_DURATION = 60000; // 1분 캐시

  async function getCoupons() {
    try {
      const now = Date.now();
      if (couponsCache && now - couponsCacheTime < CACHE_DURATION) {
        return couponsCache;
      }

      const response = await fetch(`${BASE_URL}/coupons.php`);
      const data = await response.json();

      if (data.success && data.coupons) {
        // DB 필드명을 JavaScript 필드명으로 변환
        couponsCache = data.coupons.map((c) => ({
          id: c.id,
          code: c.code,
          name: c.name,
          type: c.type,
          value: c.value,
          minAmount: c.min_amount,
          maxDiscount: c.max_discount,
          startDate: c.start_date || "",
          endDate: c.end_date || "",
          active: c.active == 1,
          usageLimit: c.usage_limit,
          usedCount: c.used_count,
          createdAt: c.created_at ? c.created_at.split(" ")[0] : "",
        }));
        couponsCacheTime = now;
        return couponsCache;
      }
      return [];
    } catch (error) {
      console.error("쿠폰 목록 조회 실패:", error);
      return [];
    }
  }

  function setCoupons(coupons) {
    // DB에 저장되므로 더 이상 localStorage 사용 안 함
    // 캐시만 업데이트
    couponsCache = coupons;
    couponsCacheTime = Date.now();
  }

  function clearCouponsCache() {
    couponsCache = null;
    couponsCacheTime = 0;
  }

  async function getActiveCoupons() {
    const coupons = await getCoupons();
    const now = new Date().toISOString().split("T")[0];
    return coupons.filter((c) => {
      if (!c.active) return false;
      if (c.startDate && c.startDate > now) return false;
      if (c.endDate && c.endDate < now) return false;
      if (c.usageLimit > 0 && c.usedCount >= c.usageLimit) return false;
      return true;
    });
  }

  async function validateCoupon(code, amount) {
    try {
      const response = await fetch(`${BASE_URL}/coupons.php?action=validate`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        credentials: "include",
        body: JSON.stringify({ code, amount }),
      });

      const data = await response.json();

      if (data.success && data.valid) {
        // DB 필드명 변환
        const coupon = data.coupon;
        return {
          valid: true,
          coupon: {
            id: coupon.id,
            code: coupon.code,
            name: coupon.name,
            type: coupon.type,
            value: coupon.value,
            minAmount: coupon.min_amount,
            maxDiscount: coupon.max_discount,
            startDate: coupon.start_date || "",
            endDate: coupon.end_date || "",
            active: coupon.active == 1,
            usageLimit: coupon.usage_limit,
            usedCount: coupon.used_count,
          },
        };
      }

      return {
        valid: false,
        message: data.message || "유효하지 않은 쿠폰입니다.",
      };
    } catch (error) {
      console.error("쿠폰 검증 실패:", error);
      return { valid: false, message: "쿠폰 검증 중 오류가 발생했습니다." };
    }
  }

  function applyCoupon(coupon, amount) {
    let discount = 0;
    if (coupon.type === "percent") {
      discount = Math.floor((amount * coupon.value) / 100);
      if (coupon.maxDiscount > 0 && discount > coupon.maxDiscount) {
        discount = coupon.maxDiscount;
      }
    } else {
      discount = coupon.value;
    }
    return Math.min(discount, amount); // 할인금액이 주문금액을 초과하지 않도록
  }

  // ========== 공지사항/이벤트 관리 ==========
  let noticesCache = null;
  let noticesCacheTime = 0;
  const NOTICES_CACHE_DURATION = 60000; // 1분 캐시

  async function getNotices() {
    try {
      const now = Date.now();
      if (noticesCache && now - noticesCacheTime < NOTICES_CACHE_DURATION) {
        return noticesCache;
      }
      
      const notices = await getJSON("/notices.php");
      noticesCache = notices || [];
      noticesCacheTime = now;
      return noticesCache;
    } catch (e) {
      console.error("공지사항 조회 오류:", e);
      return [];
    }
  }
  
  async function setNotices(notices) {
    try {
      await postJSON("/notices.php", notices);
      noticesCache = notices;
      noticesCacheTime = Date.now();
    } catch (e) {
      console.error("공지사항 저장 오류:", e);
      throw e;
    }
  }
  
  async function getActiveNotices() {
    try {
      const notices = await getNotices();
      const now = new Date().toISOString().split("T")[0];
      return notices
        .filter((n) => {
          if (!n.active && n.active !== 0) return false;
          if (n.startDate && n.startDate > now) return false;
          if (n.endDate && n.endDate < now) return false;
          return true;
        })
        .sort((a, b) => {
          // 이벤트가 공지사항보다 먼저, 그 다음 날짜순
          if (a.type !== b.type) return a.type === "event" ? -1 : 1;
          return new Date(b.createdAt || 0) - new Date(a.createdAt || 0);
        });
    } catch (e) {
      console.error("활성 공지사항 조회 오류:", e);
      return [];
    }
  }

  // ========== 사이트 설정 ==========
  let siteSettingsCache = null;
  let siteSettingsCacheTime = 0;
  const SITE_SETTINGS_CACHE_DURATION = 300000; // 5분 캐시

  async function getSiteSettings() {
    try {
      const now = Date.now();
      if (siteSettingsCache && now - siteSettingsCacheTime < SITE_SETTINGS_CACHE_DURATION) {
        return siteSettingsCache;
      }
      
      const settings = await getJSON("/settings.php");
      siteSettingsCache = settings || {};
      siteSettingsCacheTime = now;
      return siteSettingsCache;
    } catch (e) {
      console.error("사이트 설정 조회 오류:", e);
      return {};
    }
  }
  
  async function setSiteSettings(settings) {
    try {
      await putJSON("/settings.php", settings);
      siteSettingsCache = settings;
      siteSettingsCacheTime = Date.now();
    } catch (e) {
      console.error("사이트 설정 저장 오류:", e);
      throw e;
    }
  }

  // ========== 감정 섹션 관리 ==========
  let emotionsCache = null;
  let emotionsCacheTime = 0;
  const EMOTIONS_CACHE_DURATION = 60000; // 1분 캐시

  async function getEmotions() {
    try {
      const now = Date.now();
      if (emotionsCache && now - emotionsCacheTime < EMOTIONS_CACHE_DURATION) {
        return emotionsCache;
      }
      
      const emotions = await getJSON("/emotions.php");
      emotionsCache = emotions || [];
      emotionsCacheTime = now;
      return emotionsCache;
    } catch (e) {
      console.error("감정 조회 오류:", e);
      return [];
    }
  }
  
  async function setEmotions(emotions) {
    try {
      await postJSON("/emotions.php", emotions);
      emotionsCache = emotions;
      emotionsCacheTime = Date.now();
    } catch (e) {
      console.error("감정 저장 오류:", e);
      throw e;
    }
  }
  
  async function getActiveEmotions() {
    try {
      const emotions = await getEmotions();
      return emotions
        .filter((e) => e.active && e.active !== 0)
        .sort((a, b) => (a.order || 0) - (b.order || 0));
    } catch (e) {
      console.error("활성 감정 조회 오류:", e);
      return [];
    }
  }

  // ========== 섹션 타이틀 관리 ==========
  let sectionsCache = null;
  let sectionsCacheTime = 0;
  const SECTIONS_CACHE_DURATION = 300000; // 5분 캐시

  async function getSections() {
    try {
      const now = Date.now();
      if (sectionsCache && now - sectionsCacheTime < SECTIONS_CACHE_DURATION) {
        return sectionsCache;
      }
      
      const sections = await getJSON("/sections.php");
      sectionsCache = sections || {};
      sectionsCacheTime = now;
      return sectionsCache;
    } catch (e) {
      console.error("섹션 조회 오류:", e);
      return {};
    }
  }
  
  async function setSections(sections) {
    try {
      await putJSON("/sections.php", sections);
      sectionsCache = sections;
      sectionsCacheTime = Date.now();
    } catch (e) {
      console.error("섹션 저장 오류:", e);
      throw e;
    }
  }

  // ========== 감정별 추천 상품 관리 ==========
  const EMOTION_RECOMMENDATIONS_KEY = "dewscent_emotion_recommendations";
  const WEEKLY_RECOMMENDATIONS_KEY = "dewscent_weekly_recommendations";

  // 7일 주기 랜덤 추천 시드 생성
  function getWeeklySeed() {
    const daysSinceEpoch = Math.floor(Date.now() / (1000 * 60 * 60 * 24));
    const weekNumber = Math.floor(daysSinceEpoch / 7);
    return weekNumber;
  }

  // 7일 주기 랜덤 추천 가져오기/저장
  function getWeeklyRecommendations() {
    try {
      const stored = localStorage.getItem(WEEKLY_RECOMMENDATIONS_KEY);
      if (stored) {
        const data = JSON.parse(stored);
        // 주간 시드가 동일하면 캐시된 데이터 사용
        if (data.weekSeed === getWeeklySeed()) {
          return data.recommendations;
        }
      }
    } catch (e) {
      console.error("Error reading weekly recommendations:", e);
    }
    return null;
  }

  function setWeeklyRecommendations(emotionKey, productIds) {
    try {
      const stored = localStorage.getItem(WEEKLY_RECOMMENDATIONS_KEY);
      let data = stored
        ? JSON.parse(stored)
        : { weekSeed: getWeeklySeed(), recommendations: {} };

      // 주간 시드가 다르면 리셋
      if (data.weekSeed !== getWeeklySeed()) {
        data = { weekSeed: getWeeklySeed(), recommendations: {} };
      }

      data.recommendations[emotionKey] = productIds;
      localStorage.setItem(WEEKLY_RECOMMENDATIONS_KEY, JSON.stringify(data));
    } catch (e) {
      console.error("Error saving weekly recommendations:", e);
    }
  }

  // 감정별 추천 상품 가져오기
  // limit: 메인 팝업에서는 4개, 전체보기에서는 10개
  async function getEmotionRecommendations(emotionKey, limit = 4) {
    // 실제 DB에서 상품 목록 가져오기
    let allProducts = [];
    try {
      allProducts = await getJSON("/products.php");
      // DB에서 가져온 상품을 localStorage 형식에 맞게 변환
      allProducts = allProducts
        .map((p) => {
          // emotion_keys를 배열로 변환
          let emotionKeys = [];
          if (p.emotion_keys) {
            try {
              emotionKeys =
                typeof p.emotion_keys === "string"
                  ? JSON.parse(p.emotion_keys)
                  : p.emotion_keys;
              if (!Array.isArray(emotionKeys)) emotionKeys = [];
            } catch (e) {
              emotionKeys = [];
            }
          } else if (p.emotionKeys) {
            emotionKeys = Array.isArray(p.emotionKeys)
              ? p.emotionKeys
              : [p.emotionKeys];
          }

          // 이미지 URL 정리 (null, 빈 문자열, 'null' 문자열 제외)
          let imageUrl = "";
          const rawImage = p.image || p.imageUrl || "";
          if (
            rawImage &&
            rawImage !== null &&
            rawImage !== "" &&
            rawImage !== "null" &&
            rawImage !== "NULL" &&
            typeof rawImage === "string" &&
            rawImage.trim().length > 10
          ) {
            imageUrl = rawImage.trim();
          }

          let detailImageUrl = "";
          const rawDetailImage = p.detail_image || p.detailImageUrl || "";
          if (
            rawDetailImage &&
            rawDetailImage !== null &&
            rawDetailImage !== "" &&
            rawDetailImage !== "null" &&
            rawDetailImage !== "NULL" &&
            typeof rawDetailImage === "string" &&
            rawDetailImage.trim().length > 10
          ) {
            detailImageUrl = rawDetailImage.trim();
          }

          return {
            ...p,
            category: p.type || p.category || "향수",
            imageUrl: imageUrl,
            detailImageUrl: detailImageUrl,
            fragranceType: p.fragrance_type || p.fragranceType || null,
            emotionKeys: emotionKeys,
          };
        })
        .filter((p) => p.status === "판매중");
    } catch (e) {
      console.error("상품 목록 로드 실패:", e);
      // 실패하면 localStorage에서 가져오기
      allProducts = getStoredProducts().filter((p) => p.status === "판매중");
    }

    if (allProducts.length === 0) {
      return [];
    }

    // 관리자가 설정한 추천 상품이 있으면 사용 (DB에서 가져오기)
    try {
      const recommendations = await getJSON(`/emotion-recommendations.php?emotion_key=${emotionKey}`);
      if (Array.isArray(recommendations) && recommendations.length > 0) {
        // product_id 목록 추출
        const productIds = recommendations
          .sort((a, b) => (a.order || 0) - (b.order || 0))
          .map(item => item.productId);
        
        const uniqueIds = [...new Set(productIds)];
        const recommendedProducts = uniqueIds
          .map((id) =>
            allProducts.find((p) => p.id === id || p.id === parseInt(id))
          )
          .filter((p) => p && p.status === "판매중")
          .filter(
            (p, index, self) =>
              index === self.findIndex((prod) => prod.id === p.id)
          );

        if (recommendedProducts.length > 0) {
          // 관리자에서 선택한 순서대로 반환 (limit 적용)
          return recommendedProducts.slice(0, limit);
        }
      }
    } catch (e) {
      console.error("Error getting recommendations from DB:", e);
    }

    // 관리자 설정이 없으면 7일 주기 랜덤 추천 사용
    // 상품에 설정된 emotion_keys에 해당 감정이 포함된 상품만 반환
    const emotionMatchedProducts = allProducts.filter((p) => {
      if (
        !p.emotionKeys ||
        !Array.isArray(p.emotionKeys) ||
        p.emotionKeys.length === 0
      ) {
        return false;
      }
      // emotion_keys 배열에 해당 감정이 포함되어 있는지 확인
      return p.emotionKeys.includes(emotionKey);
    });

    if (emotionMatchedProducts.length > 0) {
      // 7일 주기 랜덤 추천: 같은 주에는 같은 순서 유지
      const weeklyRecs = getWeeklyRecommendations();
      if (weeklyRecs && weeklyRecs[emotionKey]) {
        // 캐시된 주간 추천이 있으면 사용
        const cachedIds = weeklyRecs[emotionKey];
        const cachedProducts = cachedIds
          .map((id) => emotionMatchedProducts.find((p) => p.id === id))
          .filter((p) => p);

        // 캐시된 상품이 limit 이상 있으면 반환
        if (cachedProducts.length >= limit) {
          return cachedProducts.slice(0, limit);
        }
      }

      // 7일 주기 시드 기반 셔플
      const weeklySeed = getWeeklySeed();
      const emotionSeed = emotionKey
        .split("")
        .reduce((acc, c) => acc + c.charCodeAt(0), 0);
      const combinedSeed = weeklySeed * 1000 + emotionSeed;

      const shuffled = [...emotionMatchedProducts].sort((a, b) => {
        const hashA = (a.id * combinedSeed) % 10000;
        const hashB = (b.id * combinedSeed) % 10000;
        return hashA - hashB;
      });

      // 주간 추천 캐시 저장
      const productIds = shuffled
        .slice(0, Math.max(limit, 10))
        .map((p) => p.id);
      setWeeklyRecommendations(emotionKey, productIds);

      return shuffled.slice(0, limit);
    }

    // emotion_keys 매칭도 없으면 카테고리 기반 7일 주기 랜덤 추천
    const emotionCategoryMap = {
      calm: ["향수", "디퓨저"],
      warm: ["향수", "바디미스트"],
      fresh: ["바디미스트", "섬유유연제", "헤어미스트"],
      romantic: ["향수", "바디미스트"],
      focus: ["향수", "디퓨저"],
      refresh: ["바디미스트", "섬유유연제", "룸스프레이"],
    };

    const categories = emotionCategoryMap[emotionKey] || ["향수"];
    let filtered = allProducts.filter(
      (p) => categories.includes(p.category) || categories.includes(p.type)
    );

    if (filtered.length === 0) {
      filtered = allProducts;
    }

    if (filtered.length > 0) {
      const weeklySeed = getWeeklySeed();
      const emotionSeed = emotionKey
        .split("")
        .reduce((acc, c) => acc + c.charCodeAt(0), 0);
      const combinedSeed = weeklySeed * 1000 + emotionSeed;

      const shuffled = [...filtered].sort((a, b) => {
        const hashA = (a.id * combinedSeed) % 10000;
        const hashB = (b.id * combinedSeed) % 10000;
        return hashA - hashB;
      });

      return shuffled.slice(0, limit);
    }

    return [];
  }

  // 완전 랜덤으로 상품 선택 (중복 제거, 매번 다른 결과)
  function getRandomProducts(products, emotionKey, count = 4) {
    // 중복 제거: id 기준으로 고유한 상품만 유지
    const uniqueProducts = products.filter(
      (p, index, self) => index === self.findIndex((prod) => prod.id === p.id)
    );

    if (uniqueProducts.length <= count) return uniqueProducts;

    // Fisher-Yates 셔플 알고리즘 (완전 랜덤)
    const shuffled = [...uniqueProducts];
    for (let i = shuffled.length - 1; i > 0; i--) {
      const j = Math.floor(Math.random() * (i + 1));
      [shuffled[i], shuffled[j]] = [shuffled[j], shuffled[i]];
    }

    // 중복 없이 count개 반환
    const result = [];
    const seenIds = new Set();
    for (const product of shuffled) {
      if (!seenIds.has(product.id)) {
        result.push(product);
        seenIds.add(product.id);
        if (result.length >= count) break;
      }
    }

    return result;
  }

  // 7일 주기로 상품 순환 (중복 제거) - 이전 버전 호환용
  function getWeeklyRotatedProducts(products, emotionKey) {
    // 중복 제거: id 기준으로 고유한 상품만 유지
    const uniqueProducts = products.filter(
      (p, index, self) => index === self.findIndex((prod) => prod.id === p.id)
    );

    if (uniqueProducts.length <= 4) return uniqueProducts.slice(0, 4);

    const daysSinceEpoch = Math.floor(Date.now() / (1000 * 60 * 60 * 24));
    const weekCycle = Math.floor(daysSinceEpoch / 7);
    const seed = weekCycle + emotionKey.charCodeAt(0);

    // 시드 기반 셔플
    const shuffled = [...uniqueProducts].sort((a, b) => {
      const hashA = (a.id * seed) % 1000;
      const hashB = (b.id * seed) % 1000;
      return hashA - hashB;
    });

    // 중복 없이 4개 반환
    const result = [];
    const seenIds = new Set();
    for (const product of shuffled) {
      if (!seenIds.has(product.id)) {
        result.push(product);
        seenIds.add(product.id);
        if (result.length >= 4) break;
      }
    }

    return result;
  }

  // DB 상품 기반 자동 추천 (매번 랜덤)
  function getAutoEmotionRecommendationsFromDB(allProducts, emotionKey) {
    // 중복 제거: id 기준으로 고유한 상품만 유지
    const uniqueProducts = allProducts.filter(
      (p, index, self) => index === self.findIndex((prod) => prod.id === p.id)
    );

    // 감정별 카테고리 매핑
    const emotionCategoryMap = {
      calm: ["향수", "디퓨저"],
      warm: ["향수", "바디미스트"],
      fresh: ["바디미스트", "섬유유연제", "헤어미스트"],
      romantic: ["향수", "바디미스트"],
      focus: ["향수", "디퓨저"],
      refresh: ["바디미스트", "섬유유연제", "룸스프레이"],
    };

    const categories = emotionCategoryMap[emotionKey] || ["향수"];
    let filtered = uniqueProducts.filter(
      (p) => categories.includes(p.category) || categories.includes(p.type)
    );

    // 카테고리 매칭이 안 되면 전체 상품에서 랜덤
    if (filtered.length === 0) {
      filtered = uniqueProducts;
    }

    return getRandomProducts(filtered, emotionKey, 4);
  }

  // 자동 추천 (관리자 설정이 없을 때) - 이전 버전 호환용
  function getAutoEmotionRecommendations(emotionKey) {
    const allProducts = getStoredProducts().filter(
      (p) => p.status === "판매중"
    );

    // 중복 제거: id 기준으로 고유한 상품만 유지
    const uniqueProducts = allProducts.filter(
      (p, index, self) => index === self.findIndex((prod) => prod.id === p.id)
    );

    // 감정별 카테고리 매핑
    const emotionCategoryMap = {
      calm: ["향수", "디퓨저"],
      warm: ["향수", "바디미스트"],
      fresh: ["바디미스트", "섬유유연제"],
      romantic: ["향수", "바디미스트"],
      focus: ["향수", "디퓨저"],
      refresh: ["바디미스트", "섬유유연제"],
    };

    const categories = emotionCategoryMap[emotionKey] || ["향수"];
    let filtered = uniqueProducts.filter((p) =>
      categories.includes(p.category)
    );

    if (filtered.length === 0) {
      filtered = uniqueProducts; // 카테고리 매칭 실패 시 전체 상품
    }

    return getRandomProducts(filtered, emotionKey, 4);
  }

  // 감정별 추천 상품 설정 (관리자용)
  async function setEmotionRecommendations(emotionKey, productIds) {
    try {
      // 중복 제거: Set을 사용하여 고유한 ID만 저장
      const uniqueIds = [...new Set(productIds)];
      await postJSON("/emotion-recommendations.php", {
        emotionKey: emotionKey,
        productIds: uniqueIds
      });
    } catch (e) {
      console.error("Error setting recommendations:", e);
      throw e;
    }
  }

  // 모든 감정별 추천 상품 가져오기 (관리자용)
  async function getAllEmotionRecommendations() {
    try {
      const recommendations = await getJSON("/emotion-recommendations.php");
      // DB 형식을 JavaScript 형식으로 변환
      const result = {};
      if (Array.isArray(recommendations)) {
        recommendations.forEach(item => {
          if (!result[item.emotionKey]) {
            result[item.emotionKey] = { productIds: [], updatedAt: null };
          }
          result[item.emotionKey].productIds.push(item.productId);
        });
      }
      return result;
    } catch (e) {
      console.error("Error getting recommendations:", e);
      return {};
    }
  }

  w.API = {
    getProfile,
    getOrders,
    getUsers,
    getAdminOrders,
    createOrder,
    updateOrderStatus,
    requestOrderCancel,
    confirmPayment,
    approveCancel,
    rejectCancel,
    // 문의 관련
    getInquiries,
    createInquiry,
    updateInquiryAnswer,
    // 리뷰 관련
    getReviews,
    createReview,
    deleteReview,
    // 상품 관련
    getProducts,
    getProduct,
    createProduct,
    updateProduct,
    deleteProduct,
    getPublicProducts,
    // 배너/캐러셀
    getBanners,
    setBanners,
    getActiveBanners,
    // 팝업
    getPopups,
    setPopups,
    getActivePopups,
    // 메인 상품 배치
    getMainProductIds,
    setMainProductIds,
    // 사이트 설정
    getSiteSettings,
    setSiteSettings,
    // 감정 섹션
    getEmotions,
    setEmotions,
    getActiveEmotions,
    // 섹션 타이틀
    getSections,
    setSections,
    // 감정별 추천 상품
    getEmotionRecommendations,
    setEmotionRecommendations,
    getAllEmotionRecommendations,
    // 쿠폰
    getCoupons,
    setCoupons,
    clearCouponsCache,
    getActiveCoupons,
    validateCoupon,
    applyCoupon,
    // 공지사항/이벤트
    getNotices,
    setNotices,
    getActiveNotices,
    // 배송 추적 시뮬레이션
    simulateShipping: function (orderId) {
      const ORDER_DETAILS_KEY = "dewscent_order_details";
      let orderDetails = {};
      try {
        const stored = localStorage.getItem(ORDER_DETAILS_KEY);
        if (stored) orderDetails = JSON.parse(stored);
      } catch {}

      const order = orderDetails[orderId];
      if (!order || !order.tracking) return order;

      const now = new Date();
      const today = now.toISOString().split("T")[0];
      const time = `${String(now.getHours()).padStart(2, "0")}:${String(
        now.getMinutes()
      ).padStart(2, "0")}`;

      // 주문일로부터 경과 시간 계산 (시뮬레이션)
      const orderDate = new Date(order.orderedAt);
      const daysDiff = Math.floor((now - orderDate) / (1000 * 60 * 60 * 24));

      // 배송 상태 업데이트 (시뮬레이션)
      if (order.status === "결제대기") {
        // 결제 완료 시뮬레이션 (1일 후)
        if (daysDiff >= 1) {
          order.status = "결제완료";
          order.tracking.history.push({
            status: "결제완료",
            date: today,
            time: time,
            message: "결제가 완료되었습니다.",
          });
        }
      } else if (order.status === "결제완료") {
        // 배송 준비 시뮬레이션 (2일 후)
        if (daysDiff >= 2) {
          order.status = "배송준비중";
          order.tracking.history.push({
            status: "배송준비중",
            date: today,
            time: time,
            message: "상품을 포장하고 있습니다.",
          });
        }
      } else if (order.status === "배송준비중") {
        // 배송 시작 시뮬레이션 (3일 후)
        if (daysDiff >= 3 && !order.tracking.number) {
          order.status = "배송중";
          // 운송장 번호 생성 (시뮬레이션)
          order.tracking.number = `1234567890${String(order.id).slice(-4)}`;
          order.tracking.history.push({
            status: "배송중",
            date: today,
            time: time,
            message: "배송이 시작되었습니다.",
          });
        }
      } else if (order.status === "배송중") {
        // 배송 완료 시뮬레이션 (5일 후)
        if (daysDiff >= 5) {
          order.status = "배송완료";
          order.tracking.history.push({
            status: "배송완료",
            date: today,
            time: time,
            message: "배송이 완료되었습니다.",
          });
        } else if (daysDiff >= 4) {
          // 배송 중간 업데이트
          const lastUpdate =
            order.tracking.history[order.tracking.history.length - 1];
          if (
            lastUpdate &&
            lastUpdate.status === "배송중" &&
            lastUpdate.date !== today
          ) {
            order.tracking.history.push({
              status: "배송중",
              date: today,
              time: time,
              message: "배송 중입니다.",
            });
          }
        }
      }

      // 주문 상세 정보 업데이트
      orderDetails[orderId] = order;
      localStorage.setItem(ORDER_DETAILS_KEY, JSON.stringify(orderDetails));

      // 주문 목록도 업데이트
      const ORDER_ADDS_KEY = "dewscent_order_adds";
      let adds = [];
      try {
        const stored = localStorage.getItem(ORDER_ADDS_KEY);
        if (stored) adds = JSON.parse(stored);
      } catch {}

      const orderInList = adds.find((o) => o.id === orderId);
      if (orderInList) {
        orderInList.status = order.status;
        localStorage.setItem(ORDER_ADDS_KEY, JSON.stringify(adds));
      }

      return order;
    },
    // 유틸
    __MOCK__: mock,
    __getStoredProducts: getStoredProducts,
    __setStoredProducts: setStoredProducts,
  };
})(window);
