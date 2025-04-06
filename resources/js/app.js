import 'flowbite';
import 'sortablejs';
import Chart from 'chart.js/auto';

// Global tanımlama
window.global = window;
window.Chart = Chart;

// Tarih formatı yardımcı fonksiyonu
window.formatDateToYYYYMMDD = function(dateStr) {
    if (!dateStr) return '';
    
    try {
        // Eğer tarih zaten yyyy-MM-dd formatındaysa dokunma
        if (/^\d{4}-\d{2}-\d{2}$/.test(dateStr)) {
            return dateStr;
        }
        
        // Tarih nesnesine çevir
        const date = new Date(dateStr);
        if (isNaN(date.getTime())) {
            console.error('Geçersiz tarih:', dateStr);
            return dateStr; // Geçersiz tarih, olduğu gibi döndür
        }
        
        // yyyy-MM-dd formatına çevir
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        
        return `${year}-${month}-${day}`;
    } catch (error) {
        console.error('Tarih formatı düzeltme hatası:', error);
        return dateStr; // Hata durumunda orijinal değeri döndür
    }
};

// Sidebar yönetimi için global state
let sidebarOpen = false;

// Sidebar toggle fonksiyonu
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const hamburger = document.getElementById('toggleSidebarMobileHamburger');
    const close = document.getElementById('toggleSidebarMobileClose');
    
    sidebarOpen = !sidebarOpen;
    sidebar.classList.toggle('-translate-x-full');
    hamburger.classList.toggle('hidden');
    close.classList.toggle('hidden');
}

// Sidebar event listeners'ları yeniden ekle
function initSidebarListeners() {
    const toggleButton = document.getElementById('toggleSidebarMobile');
    
    // Önceki event listener'ı kaldır
    toggleButton?.removeEventListener('click', handleToggleClick);
    
    // Yeni event listener ekle
    toggleButton?.addEventListener('click', handleToggleClick);
}

// Toggle click handler
function handleToggleClick(e) {
    e.stopPropagation();
    toggleSidebar();
}

// Click outside handler
function handleClickOutside(event) {
    const sidebar = document.getElementById('sidebar');
    const toggleButton = document.getElementById('toggleSidebarMobile');

    if (sidebar && !sidebar.contains(event.target) && !toggleButton?.contains(event.target)) {
        if (!sidebar.classList.contains('-translate-x-full') && window.innerWidth < 1024) {
            toggleSidebar();
        }
    }
}

// Sayfa ilk yüklendiğinde
document.addEventListener('DOMContentLoaded', () => {
    initSidebarListeners();
    document.addEventListener('click', handleClickOutside);
});

// Livewire navigasyonlarında
document.addEventListener('livewire:navigated', () => {
    console.log('Livewire navigasyon olayı tetiklendi');
    
    // Mobilde sayfa geçişlerinde sidebar'ı kapat
    const sidebar = document.getElementById('sidebar');
    if (window.innerWidth < 1024 && !sidebar.classList.contains('-translate-x-full')) {
        toggleSidebar();
    }
    
    // Event listener'ları yeniden ekle
    initSidebarListeners();

    // Livewire state'ini koru ve yeniden başlat
    if (typeof Livewire !== 'undefined') {


        // Tüm JavaScript event listener'ları yeniden ekle
        document.querySelectorAll('[x-data]').forEach(element => {
            if (typeof Alpine !== 'undefined') {
                Alpine.initTree(element);
            }
        });
    }
});

// ESC tuşu ile kapatma
document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        const sidebar = document.getElementById('sidebar');
        if (window.innerWidth < 1024 && !sidebar.classList.contains('-translate-x-full')) {
            toggleSidebar();
        }
    }
});

// Flowbite'ı başlatma fonksiyonu
function initFlowbite() {
    const dropdownButtons = document.querySelectorAll('[data-dropdown-toggle]');
    dropdownButtons.forEach(button => {
        const targetId = button.getAttribute('data-dropdown-toggle');
        const target = document.getElementById(targetId);
        
        if (button && target) {
            const dropdown = new Dropdown(target, button);
            dropdown.init();
        }
    });
}

// Nakit akışı grafiği için global değişkenler
window.cashFlowChart = null;

// Nakit akışı grafiğini oluşturma fonksiyonu
window.createCashFlowChart = function(forceRefresh = false) {
    console.log('createCashFlowChart çağrıldı, forceRefresh:', forceRefresh);
    
    // Canvas elementini kontrol et - 5 kez deneme yap
    let ctx = document.getElementById('cash-flow-chart');
    let attempts = 0;
    
    // Canvas bulunamazsa, kısa bir süre bekleyip tekrar dene
    if (!ctx) {
        console.log('Grafik canvas elementi bulunamadı, tekrar deneniyor...');
        const checkCanvas = setInterval(() => {
            ctx = document.getElementById('cash-flow-chart');
            attempts++;
            
            if (ctx || attempts >= 5) {
                clearInterval(checkCanvas);
                if (!ctx) {
                    console.log('Grafik canvas elementi 5 denemeden sonra bulunamadı');
                    return;
                }
                // Canvas bulundu, grafiği oluştur
                initializeChart(ctx, forceRefresh);
            }
        }, 100);
        return;
    }
    
    // Grafiği başlatma fonksiyonu
    function initializeChart(ctx, forceRefresh) {
        // Livewire bileşenini kontrol et
        const livewireComponent = window.Livewire.find(ctx.closest('[wire\\:id]')?.getAttribute('wire:id'));
        if (!livewireComponent) {
            console.log('Livewire bileşeni bulunamadı');
            return;
        }
        
        // Önceki grafiği temizle
        if (window.cashFlowChart instanceof Chart) {
            if (!forceRefresh) {
                console.log('Grafik zaten var ve forceRefresh false, güncelleme yapılmıyor');
                // Yükleme göstergesini gizle
                document.dispatchEvent(new CustomEvent('chartRendered'));
                return;
            }
            window.cashFlowChart.destroy();
            window.cashFlowChart = null;
        }
        
        try {
            // Veri hazırlama
            const chartData = livewireComponent.chartData || {};
            const labels = chartData.labels || [];
            const inflowData = chartData.inflowData || [];
            const outflowData = chartData.outflowData || [];
            const netData = chartData.netData || [];
            const chartType = livewireComponent.chartType || 'line';
            
            // Veri yoksa çık
            if (labels.length === 0) {
                console.log('Grafik için veri bulunamadı');
                return;
            }
            
            // Grafik tipini göster
            const chartTypeText = {
                'line': 'Çizgi Grafik',
                'bar': 'Çubuk Grafik',
                'stacked': 'Yığılmış Grafik'
            }[chartType] || chartType;
            
            const chartTypeElement = document.getElementById('current-chart-type');
            if (chartTypeElement) {
                chartTypeElement.textContent = 'Grafik Tipi: ' + chartTypeText;
            }
            
            console.log('Grafik oluşturuluyor, tip:', chartType, 'veri sayısı:', labels.length);
            
            // Grafik konfigürasyonu
            const config = {
                type: chartType === 'stacked' ? 'bar' : chartType,
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Gelir',
                            data: inflowData,
                            backgroundColor: 'rgba(74, 222, 128, 0.5)',
                            borderColor: 'rgb(74, 222, 128)',
                            borderWidth: 2,
                            tension: 0.1
                        },
                        {
                            label: 'Gider',
                            data: outflowData,
                            backgroundColor: 'rgba(248, 113, 113, 0.5)',
                            borderColor: 'rgb(248, 113, 113)',
                            borderWidth: 2,
                            tension: 0.1
                        },
                        {
                            label: 'Net Nakit Akışı',
                            data: netData,
                            backgroundColor: 'rgba(96, 165, 250, 0.5)',
                            borderColor: 'rgb(96, 165, 250)',
                            borderWidth: 2,
                            tension: 0.1
                            // Net nakit akışını tüm grafik tiplerinde göster
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: {
                        duration: 500 // Animasyon süresini kısalt
                    },
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    if (context.parsed.y !== null) {
                                        label += new Intl.NumberFormat('tr-TR', { 
                                            style: 'currency', 
                                            currency: 'TRY',
                                            minimumFractionDigits: 2
                                        }).format(context.parsed.y);
                                    }
                                    return label;
                                }
                            }
                        },
                        legend: {
                            position: 'top',
                        },
                        title: {
                            display: false,
                            text: 'Nakit Akışı Analizi'
                        }
                    },
                    scales: {
                        x: {
                            stacked: chartType === 'stacked',
                            grid: {
                                display: false
                            }
                        },
                        y: {
                            stacked: chartType === 'stacked',
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return new Intl.NumberFormat('tr-TR', { 
                                        style: 'currency', 
                                        currency: 'TRY',
                                        maximumFractionDigits: 0
                                    }).format(value);
                                }
                            }
                        }
                    }
                }
            };
            
            // Grafiği oluştur
            window.cashFlowChart = new Chart(ctx, config);
            console.log('Grafik başarıyla oluşturuldu');
            
            // Yükleme göstergesini gizle
            document.dispatchEvent(new CustomEvent('chartRendered'));
        } catch (error) {
            console.error('Grafik oluşturulurken hata:', error);
            // Hata durumunda da yükleme göstergesini gizle
            document.dispatchEvent(new CustomEvent('chartRendered'));
        }
    }
    
    // Canvas bulundu, hemen grafiği oluştur
    initializeChart(ctx, forceRefresh);
};

// Livewire başlatma
document.addEventListener('livewire:initialized', () => {
    console.log('Livewire initialized, olaylar dinleniyor');
    
    // cashFlowDataUpdated eventi için dinleyici
    Livewire.on('cashFlowDataUpdated', () => {
        console.log('cashFlowDataUpdated eventi alındı');
        setTimeout(() => {
            window.createCashFlowChart(true);
        }, 100);
    });
    
    // Livewire bileşeni güncellendiğinde
    Livewire.hook('commit', ({ component, commit, respond, succeed, fail }) => {
        succeed(({ snapshot, effect }) => {
            // Nakit akışı grafiği için kontrol
            const cashFlowElement = document.getElementById('cash-flow-chart');
            if (cashFlowElement && component.id === cashFlowElement.closest('[wire\\:id]')?.getAttribute('wire:id')) {
                console.log('Nakit akışı bileşeni güncellendi');
                setTimeout(() => {
                    window.createCashFlowChart(true);
                }, 100);
            }

            // Proje board'ları için kontrol
            const boardElement = document.querySelector('[data-board]');
            if (boardElement && component.id === boardElement.closest('[wire\\:id]')?.getAttribute('wire:id')) {
                console.log('Proje board bileşeni güncellendi');
                // Sortable.js'i yeniden başlat
                if (typeof Sortable !== 'undefined') {
                    window.initKanban();
                }
            }
        });
    });

    // Livewire navigasyon olaylarını dinle
    Livewire.on('navigated', () => {
        console.log('Livewire navigasyon olayı alındı');
        // Tüm Livewire bileşenlerini yeniden başlat
        window.Livewire.components.forEach(component => {
            if (component.$wire) {
                component.$wire.resume();
            }
        });

        // Sortable.js'i yeniden başlat
        if (typeof Sortable !== 'undefined') {
            window.initKanban();
        }
    });
});

// Sayfa yüklendiğinde grafiği oluştur
document.addEventListener('DOMContentLoaded', () => {
    console.log('DOMContentLoaded event tetiklendi');
    setTimeout(() => {
        const cashFlowElement = document.getElementById('cash-flow-chart');
        if (cashFlowElement) {
            console.log('Nakit akışı grafiği bulundu, oluşturuluyor');
            window.createCashFlowChart(true);
        }
    }, 500);
});

// Livewire navigasyonlarında grafiği yeniden oluştur
document.addEventListener('livewire:navigated', () => {
    console.log('livewire:navigated event tetiklendi');
    setTimeout(() => {
        const cashFlowElement = document.getElementById('cash-flow-chart');
        if (cashFlowElement) {
            console.log('Nakit akışı grafiği bulundu, yeniden oluşturuluyor');
            window.createCashFlowChart(true);
        }
    }, 500);
});

