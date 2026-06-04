import './style/main.scss'

// OTO Bot Landing Page — Vanilla JS Rendering

const app = document.getElementById('app')

// Icons (inline SVG)
const icons = {
  sound: `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 18v-6a9 9 0 0 1 18 0v6"/><path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3zM3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z"/></svg>`,
  eye: `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>`,
  zap: `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>`,
  wind: `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.7 7.7a2.5 2.5 0 1 1 1.8 4.3H2"/><path d="M9.6 4.6A2 2 0 1 1 11 8H2"/><path d="M12.6 19.4A2 2 0 1 0 14 16H2"/></svg>`,
  shield: `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>`,
  brain: `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9.5 2A2.5 2.5 0 0 1 12 4.5v15a2.5 2.5 0 0 1-4.96-.46 2.5 2.5 0 0 1-2.96-3.08 3 3 0 0 1-.34-5.58 2.5 2.5 0 0 1 1.32-4.24 2.5 2.5 0 0 1 1.98-3A2.5 2.5 0 0 1 9.5 2Z"/><path d="M14.5 2A2.5 2.5 0 0 0 12 4.5v15a2.5 2.5 0 0 0 4.96-.46 2.5 2.5 0 0 0 2.96-3.08 3 3 0 0 0 .34-5.58 2.5 2.5 0 0 0-1.32-4.24 2.5 2.5 0 0 0-1.98-3A2.5 2.5 0 0 0 14.5 2Z"/></svg>`,
  moon: `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>`,
  camera: `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>`,
  clock: `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>`,
  map: `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="1 6 1 22 8 18 16 22 23 18 23 2 16 6 8 2 1 6"/><line x1="8" y1="2" x2="8" y2="18"/><line x1="16" y1="6" x2="16" y2="22"/></svg>`,
  star: `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>`,
  chevron: `<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>`,
  close: `<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>`,
  arrow: `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>`,
  menu: `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>`,
  check: `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>`,
}

// State
const state = {
  mobileMenuOpen: false,
  scrolled: false,
  visibleSections: new Set(),
  form: { name: '', email: '', message: '', product: 'C1' }
}

// Render functions
function renderHeader() {
  const scrolled = state.scrolled ? 'scrolled' : ''
  const menuOpen = state.mobileMenuOpen ? 'open' : ''
  
  return `
    <header class="site-header ${scrolled}">
      <div class="container header-inner">
        <a href="#" class="logo">
          <img src="/LOGO/OTOb.png" alt="OTO Bot 仿生猫头鹰扑翼飞行器 Logo" width="40" height="40" />
          <span>OTO Bot</span>
        </a>
        <nav class="main-nav desktop-nav">
          <a href="#features">核心优势</a>
          <a href="#specs">技术参数</a>
          <a href="#products">产品版本</a>
          <a href="#scenarios">应用场景</a>
          <a href="#contact" class="nav-cta">立即咨询</a>
        </nav>
        <button class="mobile-toggle ${menuOpen}" onclick="toggleMobileMenu()" aria-label="菜单">
          ${state.mobileMenuOpen ? icons.close : icons.menu}
        </button>
      </div>
      <nav class="mobile-nav ${menuOpen}">
        <a href="#features" onclick="toggleMobileMenu()">核心优势</a>
        <a href="#specs" onclick="toggleMobileMenu()">技术参数</a>
        <a href="#products" onclick="toggleMobileMenu()">产品版本</a>
        <a href="#scenarios" onclick="toggleMobileMenu()">应用场景</a>
        <a href="#contact" onclick="toggleMobileMenu()">立即咨询</a>
      </nav>
    </header>
  `
}

function renderHero() {
  return `
    <section class="hero" id="home">
      <div class="hero-bg">
        <video id="hero-video" class="hero-video" autoplay muted playsinline>
          <source src="/LOGO/s1.mp4" type="video/mp4">
        </video>
        <div class="video-indicators">
          <span class="indicator active" data-index="0"></span>
          <span class="indicator" data-index="1"></span>
        </div>
        <div class="hero-video-overlay"></div>
        <div class="hero-particles" id="particles"></div>
        <div class="hero-gradient"></div>
      </div>
      <div class="container hero-content">
        <div class="hero-badge animate-in">
          <span class="badge-dot"></span>
          全球首款深度仿生猫头鹰扑翼飞行器
        </div>
        <h1 class="hero-title animate-in delay-1">
          暗夜之眼<br/>
          <span class="gradient-text">无声守护</span>
        </h1>
        <p class="hero-subtitle animate-in delay-2">
          OTO Bot 精确复刻猫头鹰数百万年进化的完美飞行机制——32dB静音飞行、星光级夜视、270°旋转头部。以仿生科技，重新定义无人机的未来。
        </p>
        <div class="hero-actions animate-in delay-3">
          <a href="#products" class="btn btn-primary btn-lg">
            探索产品
            <span class="btn-arrow">${icons.arrow}</span>
          </a>
          <a href="#features" class="btn btn-ghost btn-lg">
            了解更多
          </a>
        </div>
        <div class="hero-stats animate-in delay-4">
          <div class="stat">
            <strong>32<span>dB</span></strong>
            <span>静音飞行</span>
          </div>
          <div class="stat-divider"></div>
          <div class="stat">
            <strong>90<span>min</span></strong>
            <span>超长续航</span>
          </div>
          <div class="stat-divider"></div>
          <div class="stat">
            <strong>270<span>°</span></strong>
            <span>视野覆盖</span>
          </div>
          <div class="stat-divider"></div>
          <div class="stat">
            <strong>50<span>+</span></strong>
            <span>仿生专利</span>
          </div>
        </div>
      </div>
      <div class="hero-visual animate-in delay-2">
        <div class="hero-3d-display">
          <div class="hero-3d-glow"></div>
          <div class="hero-3d-ring ring-1"></div>
          <div class="hero-3d-ring ring-2"></div>
          <div class="hero-3d-ring ring-3"></div>
          <div class="owl-illustration">
            <div class="owl-body"></div>
            <div class="owl-eye left"></div>
            <div class="owl-eye right"></div>
            <div class="owl-wing left"></div>
            <div class="owl-wing right"></div>
            <div class="owl-ring"></div>
            <div class="owl-ring ring-2"></div>
            <div class="owl-ring ring-3"></div>
          </div>
        </div>
      </div>
      <div class="hero-scroll-hint">
        <div class="scroll-line"></div>
      </div>
    </section>
  `
}
function renderFeatures() {
  const features = [
    {
      icon: icons.sound,
      title: '静音飞行系统',
      subtitle: 'Silent Flight System',
      desc: '碳纤维+硅胶复合前缘锯齿，精确复刻大雕鸮翅膀结构。纳米多孔柔性蒙皮，吸声系数达0.85。10米距离仅32分贝，达到军用级声学隐身标准。',
      tags: ['32dB静音', '仿生降噪', '声学隐身']
    },
    {
      icon: icons.eye,
      title: '多模态感知系统',
      subtitle: 'Multi-Modal Perception',
      desc: '索尼IMX577星光级传感器，0.001lux低光彩色成像。270°可旋转头部配合双目摄像头，实现340°无死角视野。120fps高速目标跟踪，可追踪60km/h移动物体。',
      tags: ['星光夜视', '270°旋转', '智能追踪']
    },
    {
      icon: icons.brain,
      title: '智能自主飞行',
      subtitle: 'Autonomous Intelligence',
      desc: '基于大模型的自然语言任务理解，说一句话即可自动规划航线。多模态避障融合视觉、听觉和深度信息，可识别透明障碍物和声音障碍物。',
      tags: ['AI任务规划', '多模态避障', '自适应飞行']
    },
    {
      icon: icons.zap,
      title: '气动效率优势',
      subtitle: 'Aerodynamic Excellence',
      desc: '滑翔比1:12，西工大圆锥摇臂驱动机构机械效率达38%。相同重量下续航是多旋翼无人机的3倍，是其他扑翼飞行器的1.5倍。',
      tags: ['1:12滑翔比', '38%机械效率', '3倍续航']
    }
  ]
  
  return `
    <section class="features" id="features">
      <div class="container">
        <div class="section-header">
          <div class="section-tag">核心优势</div>
          <h2 class="section-title">自然界最完美的<br/>飞行机制</h2>
          <p class="section-desc">OTO Bot 不是简单的形态模仿，而是从基因层面复刻猫头鹰数百万年进化的生存智慧。</p>
        </div>
        <div class="features-grid">
          ${features.map((f, i) => `
            <div class="feature-card" data-index="${i}">
              <div class="feature-icon">${f.icon}</div>
              <div class="feature-label">${f.subtitle}</div>
              <h3 class="feature-title">${f.title}</h3>
              <p class="feature-desc">${f.desc}</p>
              <div class="feature-tags">
                ${f.tags.map(t => `<span class="tag">${t}</span>`).join('')}
              </div>
            </div>
          `).join('')}
        </div>
      </div>
    </section>
  `
}

function renderComparison() {
  const rows = [
    { label: '飞行噪声 (10m)', oto: '32 分贝', dji: '60 分贝', dan: '45 分贝', owl: '30 分贝', highlight: true },
    { label: '续航时间', oto: '90 分钟', dji: '46 分钟', dan: '60 分钟', owl: '240 分钟' },
    { label: '最大抗风', oto: '6 级', dji: '6 级', dan: '4 级', owl: '8 级' },
    { label: '夜间作业', oto: '★★★★★', dji: '★★☆☆☆', dan: '★☆☆☆☆', owl: '★★★★★' },
    { label: '隐蔽性', oto: '★★★★★', dji: '★☆☆☆☆', dan: '★★★☆☆', owl: '★★★★★' },
    { label: '声源定位', oto: '●', dji: '—', dan: '—', owl: '●' },
    { label: '头部旋转', oto: '270°', dji: '0°', dan: '90°', owl: '270°' },
    { label: '最大载重', oto: '200g', dji: '0g', dan: '50g', owl: '500g' },
  ]
  
  return `
    <section class="comparison" id="specs">
      <div class="container">
        <div class="section-header light">
          <div class="section-tag">性能对比</div>
          <h2 class="section-title">全方位超越<br/>现有飞行器</h2>
        </div>
        <div class="table-wrapper">
          <table class="compare-table">
            <thead>
              <tr>
                <th>性能指标</th>
                <th class="highlight-col">
                  <div class="product-label oto">OTO Bot P1</div>
                </th>
                <th>大疆 Mavic 3</th>
                <th>鹰瞰智翼<br/><span style="font-weight:400;font-size:0.7em;color:rgba(255,255,255,0.5)">"丹丹"</span></th>
                <th>真实大雕鸮</th>
              </tr>
            </thead>
            <tbody>
              ${rows.map((row, i) => `
                <tr class="${i % 2 === 0 ? 'even' : ''}">
                  <td>${row.label}</td>
                  <td class="${row.highlight ? 'highlight-cell' : ''}">${row.oto}</td>
                  <td>${row.dji}</td>
                  <td>${row.dan}</td>
                  <td>${row.owl}</td>
                </tr>
              `).join('')}
            </tbody>
          </table>
        </div>
      </div>
    </section>
  `
}

function renderProducts() {
  const products = [
    {
      id: 'C1',
      name: 'OTO Bot C1',
      subtitle: '消费级',
      price: '¥4,999',
      desc: '高端消费电子产品，面向科技极客、摄影爱好者和高端礼品市场。',
      features: [
        '32dB 静音飞行',
        '星光级夜视摄像头',
        '60 分钟续航',
        '手机 APP 控制',
        '智能自主飞行',
        '语音控制'
      ],
      color: '#4a9eff',
      gradient: 'linear-gradient(135deg, #1a2a4a 0%, #0d1a30 100%)',
      tag: '科技极客首选',
      popular: false
    },
    {
      id: 'P1',
      name: 'OTO Bot P1',
      subtitle: '专业级',
      price: '¥19,999',
      desc: '专业工具，面向影视制作公司、科研机构和教育机构。',
      features: [
        '32dB 静音飞行',
        '星光级夜视 + 热成像',
        '90 分钟续航',
        '专业地面站',
        '多模态感知系统',
        '200g 有效载荷',
        'API/SDK 开放接口'
      ],
      color: '#7c6aff',
      gradient: 'linear-gradient(135deg, #1a1040 0%, #0d0820 100%)',
      tag: '专业用户首选',
      popular: true
    },
    {
      id: 'I1',
      name: 'OTO Bot I1',
      subtitle: '工业级',
      price: '¥99,999',
      desc: '工业设备，面向安防公司、应急救援部门和工业企业，支持深度定制。',
      features: [
        '32dB 静音飞行',
        '星光级 + 热成像 + 声学',
        '全天候 IP54 防护',
        '-10°C 至 45°C 工作',
        '多机协同作业',
        '私有化部署',
        '专属定制服务'
      ],
      color: '#00d4aa',
      gradient: 'linear-gradient(135deg, #0a2a20 0%, #051510 100%)',
      tag: '行业用户专属',
      popular: false
    }
  ]
  
  return `
    <section class="products" id="products">
      <div class="container">
        <div class="section-header">
          <div class="section-tag">产品版本</div>
          <h2 class="section-title">三个版本<br/>满足不同需求</h2>
          <p class="section-desc">从科技爱好者到行业用户，OTO Bot 都有适合你的选择。</p>
        </div>
        <div class="products-grid">
          ${products.map(p => `
            <div class="product-card ${p.popular ? 'popular' : ''}" style="--card-color: ${p.color}; --card-gradient: ${p.gradient}">
              ${p.popular ? '<div class="popular-badge">最受欢迎</div>' : ''}
              <div class="product-top">
                <div class="product-icon" style="background: ${p.color}22; border-color: ${p.color}44">
                  <span style="color: ${p.color}">${icons.brain}</span>
                </div>
                <div class="product-edition">${p.subtitle}</div>
              </div>
              <h3 class="product-name">${p.name}</h3>
              <div class="product-price">
                <strong>${p.price}</strong>
              </div>
              <p class="product-desc">${p.desc}</p>
              <ul class="product-features">
                ${p.features.map(f => `<li><span class="check-icon">${icons.check}</span> ${f}</li>`).join('')}
              </ul>
              <div class="product-tag">${p.tag}</div>
              <a href="#contact" class="btn ${p.popular ? 'btn-primary' : 'btn-outline'}" style="--btn-color: ${p.color}">
                获取报价
                ${icons.arrow}
              </a>
            </div>
          `).join('')}
        </div>
      </div>
    </section>
  `
}

function renderScenarios() {
  const scenarios = [
    {
      icon: icons.shield,
      title: '安防与边境巡逻',
      desc: '静音飞行 + 伪装色设计，可在不被发现的情况下进行长时间夜间巡逻。声源定位可精确定位非法越境者的说话声和脚步声。',
      stats: '24/7 全天候',
      statLabel: '不间断巡逻'
    },
    {
      icon: icons.star,
      title: '生态野生动物监测',
      desc: '不会惊扰野生动物，可近距离观察动物的自然行为。90分钟续航覆盖更大范围，同时采集图像、声音和环境数据。',
      stats: '0 米',
      statLabel: '近距观察距离'
    },
    {
      icon: icons.camera,
      title: '影视与广告拍摄',
      desc: '提供与真实鸟类相同的飞行视角，创造震撼视觉效果。静音设计不干扰同期录音，可在狭窄空间内飞行。',
      stats: '120fps',
      statLabel: '高速目标跟踪'
    },
    {
      icon: icons.map,
      title: '应急救援',
      desc: '手抛起飞无需准备时间，可在灾害发生后立即投入使用。声源定位可精确定位幸存者呼救声，搜索效率提升300%。',
      stats: '< 30s',
      statLabel: '起飞准备时间'
    }
  ]
  
  return `
    <section class="scenarios" id="scenarios">
      <div class="container">
        <div class="section-header light">
          <div class="section-tag">应用场景</div>
          <h2 class="section-title">解决传统飞行器<br/>无法解决的问题</h2>
        </div>
        <div class="scenarios-grid">
          ${scenarios.map(s => `
            <div class="scenario-card">
              <div class="scenario-icon">${s.icon}</div>
              <h3 class="scenario-title">${s.title}</h3>
              <p class="scenario-desc">${s.desc}</p>
              <div class="scenario-stat">
                <strong>${s.stats}</strong>
                <span>${s.statLabel}</span>
              </div>
            </div>
          `).join('')}
        </div>
      </div>
    </section>
  `
}

function renderStory() {
  return `
    <section class="story" id="story">
      <div class="container">
        <div class="story-content">
          <div class="story-visual">
            <div class="story-img-wrapper">
              <div class="story-quote-mark">"</div>
              <div class="story-text">
                数百万年来，猫头鹰一直是黑夜的主宰。今天，我们将这种自然界的完美造物与最先进的人工智能技术相结合，创造出了 OTO Bot—— 你的暗夜之眼，无声守护。
              </div>
            </div>
          </div>
          <div class="story-right">
            <div class="section-tag">品牌故事</div>
            <h2 class="section-title">仿生具身智能<br/>第一品牌</h2>
            <div class="story-values">
              <div class="value">
                <div class="value-icon">${icons.brain}</div>
                <div>
                  <h4>仿生科技</h4>
                  <p>以自然界为师，将生物进化的智慧转化为技术创新</p>
                </div>
              </div>
              <div class="value">
                <div class="value-icon">${icons.zap}</div>
                <div>
                  <h4>智能进化</h4>
                  <p>不断学习和进化，为用户提供越来越好的体验</p>
                </div>
              </div>
              <div class="value">
                <div class="value-icon">${icons.eye}</div>
                <div>
                  <h4>和谐共生</h4>
                  <p>与自然和谐共存，用科技保护自然</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  `
}

function renderContact() {
  return `
    <section class="contact" id="contact">
      <div class="container">
        <div class="contact-inner">
          <div class="contact-info">
            <div class="section-tag">联系我们</div>
            <h2 class="section-title">开启您的<br/>无声守护之旅</h2>
            <p class="contact-desc">无论是科技爱好者还是行业用户，我们的团队随时为您提供专业的咨询和定制方案。</p>
            <div class="contact-methods">
              <div class="contact-method">
                <div class="method-icon">${icons.clock}</div>
                <div>
                  <strong>工作时间</strong>
                  <span>周一至周五 9:00 - 18:00</span>
                </div>
              </div>
              <div class="contact-method">
                <div class="method-icon">${icons.map}</div>
                <div>
                  <strong>体验中心</strong>
                  <span>北京 · 上海 · 深圳</span>
                </div>
              </div>
            </div>
          </div>
          <form class="contact-form" onsubmit="handleContactForm(event)">
            <div class="form-group">
              <label for="name">您的姓名</label>
              <input type="text" id="name" name="name" placeholder="张三" required />
            </div>
            <div class="form-group">
              <label for="email">邮箱地址</label>
              <input type="email" id="email" name="email" placeholder="zhangsan@example.com" required />
            </div>
            <div class="form-group">
              <label for="product">感兴趣的产品</label>
              <select id="product" name="product">
                <option value="C1">OTO Bot C1 — 消费级（¥4,999）</option>
                <option value="P1" selected>OTO Bot P1 — 专业级（¥19,999）</option>
                <option value="I1">OTO Bot I1 — 工业级（¥99,999）</option>
              </select>
            </div>
            <div class="form-group">
              <label for="message">您的需求</label>
              <textarea id="message" name="message" rows="4" placeholder="请描述您的使用场景和需求，我们的团队将尽快与您联系。"></textarea>
            </div>
            <button type="submit" class="btn btn-primary btn-block">
              提交咨询
              ${icons.arrow}
            </button>
            <p class="form-note">我们将在 24 小时内回复您的咨询</p>
          </form>
        </div>
      </div>
    </section>
  `
}

function renderFooter() {
  return `
    <footer class="site-footer">
      <div class="container">
        <div class="footer-grid">
          <div class="footer-brand">
            <a href="#" class="logo">
              <img src="/LOGO/OTOb.png" alt="OTO Bot 仿生猫头鹰扑翼飞行器 Logo" width="32" height="32" />
              <span>OTO Bot</span>
            </a>
            <p>全球首款深度仿生猫头鹰扑翼飞行器。<br/>仿生科技，智能进化，和谐共生。</p>
          </div>
          <div class="footer-col">
            <h4>产品</h4>
            <a href="#products">OTO Bot C1</a>
            <a href="#products">OTO Bot P1</a>
            <a href="#products">OTO Bot I1</a>
          </div>
          <div class="footer-col">
            <h4>支持</h4>
            <a href="#features">核心技术</a>
            <a href="#specs">技术参数</a>
            <a href="#scenarios">应用场景</a>
          </div>
          <div class="footer-col">
            <h4>关于</h4>
            <a href="#story">品牌故事</a>
            <a href="#contact">联系我们</a>
            <a href="#">隐私政策</a>
          </div>
        </div>
        <div class="footer-bottom">
          <span>© 2025 OTO Bot. 仿生具身智能第一品牌.</span>
          <div class="footer-links">
            <a href="#">使用条款</a>
            <a href="#">隐私政策</a>
          </div>
        </div>
      </div>
    </footer>
  `
}

// Main render

// Hero carousel auto-rotate
let carouselInterval = null

function initHeroVideo() {
  const videoElement = document.getElementById('hero-video')
  const indicators = document.querySelectorAll('.indicator')

  if (!videoElement) return

  const videos = [
    '/LOGO/s1.mp4',
    '/LOGO/s2.mp4'
  ]

  let currentIndex = 0

  function playVideo(index) {
    videoElement.src = videos[index]
    videoElement.load()
    videoElement.play().catch(e => console.log('Autoplay prevented:', e))

    indicators.forEach((ind, i) => {
      ind.classList.toggle('active', i === index)
    })

    currentIndex = index
  }

  videoElement.addEventListener('ended', () => {
    const nextIndex = (currentIndex + 1) % videos.length
    playVideo(nextIndex)
  })

  indicators.forEach((indicator, index) => {
    indicator.addEventListener('click', () => {
      playVideo(index)
    })
  })

  // Start with first video
  playVideo(0)
}

function render() {
  app.innerHTML = `
    ${renderHeader()}
    ${renderHero()}
    ${renderFeatures()}
    ${renderComparison()}
    ${renderProducts()}
    ${renderScenarios()}
    ${renderStory()}
    ${renderContact()}
    ${renderFooter()}
  `
  
  initScrollObserver()
  initParticles()
  initHeroVideo()
}

// Scroll handling
function handleScroll() {
  state.scrolled = window.scrollY > 50
  const header = document.querySelector('.site-header')
  if (header) {
    header.classList.toggle('scrolled', state.scrolled)
  }
}

function initScrollObserver() {
  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('visible')
      }
    })
  }, { threshold: 0.1, rootMargin: '0px 0px -50px 0px' })
  
  document.querySelectorAll('.feature-card, .scenario-card, .product-card, .compare-table, .story-content, .section-header').forEach(el => {
    el.classList.add('scroll-reveal')
    observer.observe(el)
  })
}

// Particles
function initParticles() {
  const container = document.getElementById('particles')
  if (!container) return
  for (let i = 0; i < 30; i++) {
    const p = document.createElement('div')
    p.className = 'particle'
    p.style.cssText = `
      left: ${Math.random() * 100}%;
      top: ${Math.random() * 100}%;
      width: ${Math.random() * 3 + 1}px;
      height: ${Math.random() * 3 + 1}px;
      animation-delay: ${Math.random() * 6}s;
      animation-duration: ${Math.random() * 6 + 4}s;
    `
    container.appendChild(p)
  }
}

// Mobile menu toggle
window.toggleMobileMenu = function() {
  state.mobileMenuOpen = !state.mobileMenuOpen
  const header = document.querySelector('.site-header')
  const mobileNav = document.querySelector('.mobile-nav')
  const toggle = document.querySelector('.mobile-toggle')
  if (mobileNav) mobileNav.classList.toggle('open', state.mobileMenuOpen)
  if (toggle) toggle.classList.toggle('open', state.mobileMenuOpen)
}

// Smooth scroll
window.addEventListener('scroll', handleScroll, { passive: true })
document.addEventListener('click', (e) => {
  if (e.target.matches('a[href^="#"]')) {
    e.preventDefault()
    const target = document.querySelector(e.target.getAttribute('href'))
    if (target) {
      target.scrollIntoView({ behavior: 'smooth', block: 'start' })
    }
  }
})

// Contact form
window.handleContactForm = function(e) {
  e.preventDefault()
  const form = e.target
  const btn = form.querySelector('button[type="submit"]')
  const originalText = btn.innerHTML
  btn.innerHTML = '<span style="color:#7cff7c">✓ 提交成功！我们将尽快与您联系</span>'
  btn.disabled = true
  btn.style.background = 'rgba(0,200,100,0.2)'
  setTimeout(() => {
    btn.innerHTML = originalText
    btn.disabled = false
    btn.style.background = ''
    form.reset()
  }, 4000)
}

// Init
render()

// ============================================
// 3D PRODUCT SHOWCASE
// ============================================
let showcaseState = {
  angle: 0,
  isDragging: false,
  lastX: 0,
  autoRotateTimer: null
}



