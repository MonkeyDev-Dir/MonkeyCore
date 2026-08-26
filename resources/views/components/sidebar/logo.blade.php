<div class="pt-8 pb-7 flex"
    :class="(!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen) ? 'xl:justify-center' : 'justify-start'">
    <a href="/">
        <img x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen"
            class="dark:hidden" src="/images/logo/monkey-web-bg-clear.webp" alt="Logo" width="220" height="37" />
        <img x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen"
            class="hidden dark:block" src="/images/logo/monkey-web-bg-dark.webp" alt="Logo" width="220" height="37" />
        <img x-show="!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen"
            class="dark:hidden" src="/images/logo/monkey-mini-bg-clear.webp" alt="Logo" width="40" height="25" />
        <img x-show="!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen"
            class="hidden dark:block" src="/images/logo/monkey-mini-bg-dark.webp" alt="Logo" width="40" height="25" />
    </a>
</div>
