(function (w) {
  const USE_MOCK_API = true; // false로 바꾸면 실제 API 엔드포인트로 연결
  const BASE_URL = "/api"; // 나중에 백엔드 연결 시 교체
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
      stock: 0,
      status: "품절",
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
    const res = await fetch(`${BASE_URL}${path}`, {
      credentials: "include",
      headers: { "Content-Type": "application/json" },
      ...opts,
    });
    if (!res.ok) throw new Error(`API Error: ${res.status}`);
    return await res.json();
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
    return await getJSON(`/orders${qs ? "?" + qs : ""}`);
  }

  async function getUsers() {
    if (USE_MOCK_API) {
      await delay(200);
      return mock.users;
    }
    return await getJSON("/admin/users");
  }

  async function getAdminOrders() {
    if (USE_MOCK_API) {
      await delay(200);
      return mock.adminOrders;
    }
    return await getJSON("/admin/orders");
  }

  // ========== 상품 CRUD ==========

  // 상품 목록 조회
  async function getProducts() {
    if (USE_MOCK_API) {
      await delay(100);
      return getStoredProducts();
    }
    return await getJSON("/admin/products");
  }

  // 상품 단일 조회
  async function getProduct(id) {
    if (USE_MOCK_API) {
      await delay(50);
      const products = getStoredProducts();
      return products.find((p) => p.id === id) || null;
    }
    return await getJSON(`/admin/products/${id}`);
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
    return await getJSON("/admin/products", {
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
    return await getJSON(`/admin/products/${id}`, {
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
    return await getJSON(`/admin/products/${id}`, {
      method: "DELETE",
    });
  }

  // 프론트엔드용 상품 목록 (판매중인 것만)
  async function getPublicProducts() {
    if (USE_MOCK_API) {
      await delay(50);
      const products = getStoredProducts();
      return products.filter((p) => p.status === "판매중");
    }
    return await getJSON("/products");
  }

  // ========== 배너/캐러셀 관리 ==========
  const BANNERS_KEY = "dewscent_banners";
  const defaultBanners = [
    {
      id: 1,
      title: "새로운 향기의 시작",
      subtitle: "DewScent 2025 컬렉션",
      link: "pages/products.php",
      imageUrl: "",
      order: 1,
      active: true,
    },
    {
      id: 2,
      title: "봄의 향기를 담다",
      subtitle: "벚꽃 에디션 출시",
      link: "pages/products.php",
      imageUrl: "",
      order: 2,
      active: true,
    },
    {
      id: 3,
      title: "특별한 선물",
      subtitle: "기프트 세트 20% 할인",
      link: "pages/products.php",
      imageUrl: "",
      order: 3,
      active: true,
    },
  ];

  function getBanners() {
    try {
      const stored = localStorage.getItem(BANNERS_KEY);
      if (stored) return JSON.parse(stored);
      localStorage.setItem(BANNERS_KEY, JSON.stringify(defaultBanners));
      return defaultBanners;
    } catch {
      return defaultBanners;
    }
  }
  function setBanners(banners) {
    localStorage.setItem(BANNERS_KEY, JSON.stringify(banners));
  }
  function getActiveBanners() {
    return getBanners()
      .filter((b) => b.active)
      .sort((a, b) => a.order - b.order);
  }

  // ========== 팝업 관리 ==========
  const POPUPS_KEY = "dewscent_popups";
  const defaultPopups = [
    {
      id: 1,
      title: "신규 회원 10% 할인",
      content: "첫 구매 시 10% 할인 쿠폰을 드려요!",
      link: "",
      imageUrl: "",
      order: 1,
      active: true,
      startDate: "",
      endDate: "",
    },
  ];

  function getPopups() {
    try {
      const stored = localStorage.getItem(POPUPS_KEY);
      if (stored) return JSON.parse(stored);
      localStorage.setItem(POPUPS_KEY, JSON.stringify(defaultPopups));
      return defaultPopups;
    } catch {
      return defaultPopups;
    }
  }
  function setPopups(popups) {
    localStorage.setItem(POPUPS_KEY, JSON.stringify(popups));
  }
  function getActivePopups() {
    const now = new Date().toISOString().split("T")[0];
    return getPopups()
      .filter((p) => {
        if (!p.active) return false;
        if (p.startDate && p.startDate > now) return false;
        if (p.endDate && p.endDate < now) return false;
        return true;
      })
      .sort((a, b) => a.order - b.order);
  }

  // ========== 메인 상품 배치 관리 ==========
  const MAIN_PRODUCTS_KEY = "dewscent_main_products";
  function getMainProductIds() {
    try {
      const stored = localStorage.getItem(MAIN_PRODUCTS_KEY);
      if (stored) return JSON.parse(stored);
      return []; // 비어있으면 전체 상품 중 상위 4개 표시
    } catch {
      return [];
    }
  }
  function setMainProductIds(ids) {
    localStorage.setItem(MAIN_PRODUCTS_KEY, JSON.stringify(ids));
  }

  // ========== 사이트 설정 ==========
  const SITE_SETTINGS_KEY = "dewscent_site_settings";
  const defaultSiteSettings = {
    siteName: "DewScent",
    siteSlogan: "당신의 향기를 찾아서",
    contactEmail: "hello@dewscent.kr",
    contactPhone: "02-1234-5678",
    address: "서울시 강남구 테헤란로 123",
    businessHours: "평일 10:00 ~ 17:00",
    kakaoChannel: "듀센트 고객센터",
    instagramUrl: "https://instagram.com",
  };

  function getSiteSettings() {
    try {
      const stored = localStorage.getItem(SITE_SETTINGS_KEY);
      if (stored) return { ...defaultSiteSettings, ...JSON.parse(stored) };
      return defaultSiteSettings;
    } catch {
      return defaultSiteSettings;
    }
  }
  function setSiteSettings(settings) {
    localStorage.setItem(SITE_SETTINGS_KEY, JSON.stringify(settings));
  }

  // ========== 감정 섹션 관리 ==========
  const EMOTIONS_KEY = "dewscent_emotions";
  const defaultEmotions = [
    {
      id: 1,
      key: "calm",
      title: "차분해지고 싶은 날",
      desc: "마음이 고요해지는 향",
      order: 1,
      active: true,
    },
    {
      id: 2,
      key: "warm",
      title: "따뜻함이 필요한 순간",
      desc: "포근한 온기를 담은 향",
      order: 2,
      active: true,
    },
    {
      id: 3,
      key: "fresh",
      title: "상쾌하게 시작하고 싶을 때",
      desc: "기분 좋은 청량감",
      order: 3,
      active: true,
    },
    {
      id: 4,
      key: "romantic",
      title: "설레는 마음을 담아",
      desc: "로맨틱한 플로럴 노트",
      order: 4,
      active: true,
    },
  ];

  function getEmotions() {
    try {
      const stored = localStorage.getItem(EMOTIONS_KEY);
      if (stored) return JSON.parse(stored);
      localStorage.setItem(EMOTIONS_KEY, JSON.stringify(defaultEmotions));
      return defaultEmotions;
    } catch {
      return defaultEmotions;
    }
  }
  function setEmotions(emotions) {
    localStorage.setItem(EMOTIONS_KEY, JSON.stringify(emotions));
  }
  function getActiveEmotions() {
    return getEmotions()
      .filter((e) => e.active)
      .sort((a, b) => a.order - b.order);
  }

  // ========== 섹션 타이틀 관리 ==========
  const SECTIONS_KEY = "dewscent_sections";
  const defaultSections = {
    emotionLabel: "FIND YOUR SCENT",
    emotionTitle: "오늘, 어떤 기분인가요?",
    emotionSubtitle: "감정에 맞는 향기를 추천해드릴게요",
    bestLabel: "BEST SELLERS",
    bestTitle: "가장 사랑받는 향기",
    bestSubtitle: "많은 분들이 선택한 듀센트의 베스트셀러",
    bestQuote: "— 향기는 기억을 여는 열쇠 —",
  };

  function getSections() {
    try {
      const stored = localStorage.getItem(SECTIONS_KEY);
      if (stored) return { ...defaultSections, ...JSON.parse(stored) };
      return defaultSections;
    } catch {
      return defaultSections;
    }
  }
  function setSections(sections) {
    localStorage.setItem(SECTIONS_KEY, JSON.stringify(sections));
  }

  w.API = {
    getProfile,
    getOrders,
    getUsers,
    getAdminOrders,
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
    // 유틸
    __MOCK__: mock,
    __getStoredProducts: getStoredProducts,
    __setStoredProducts: setStoredProducts,
  };
})(window);
