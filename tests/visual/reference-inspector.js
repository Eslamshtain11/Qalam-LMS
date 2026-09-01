/*
 * Paste into DevTools Console on an authorized reference page.
 * Captures computed visual properties + active CSS/Web Animations for visible elements.
 * This file is development-only and is never enqueued by Qalam LMS.
 */
(() => {
  const props = [
    'display','position','inset','width','height','minWidth','maxWidth','minHeight','maxHeight',
    'marginTop','marginRight','marginBottom','marginLeft','paddingTop','paddingRight','paddingBottom','paddingLeft',
    'gap','rowGap','columnGap','fontFamily','fontSize','fontWeight','lineHeight','letterSpacing','textAlign',
    'color','backgroundColor','backgroundImage','borderRadius','borderColor','borderWidth','boxShadow',
    'opacity','transform','transition','animationName','animationDuration','animationTimingFunction'
  ];
  const visible = el => {
    const r = el.getBoundingClientRect();
    const s = getComputedStyle(el);
    return r.width > 0 && r.height > 0 && s.visibility !== 'hidden' && s.display !== 'none';
  };
  const selector = el => {
    if (el.id) return `#${CSS.escape(el.id)}`;
    const cls = [...el.classList].slice(0,3).map(c=>'.'+CSS.escape(c)).join('');
    return `${el.tagName.toLowerCase()}${cls}`;
  };
  const nodes = [...document.querySelectorAll('body *')].filter(visible).slice(0,1200).map(el => {
    const cs = getComputedStyle(el); const rect = el.getBoundingClientRect();
    const style = {}; props.forEach(p => style[p] = cs[p]);
    const animations = el.getAnimations().map(a => ({
      currentTime:a.currentTime,
      playState:a.playState,
      timing:a.effect && a.effect.getTiming ? a.effect.getTiming() : null,
      keyframes:a.effect && a.effect.getKeyframes ? a.effect.getKeyframes() : []
    }));
    return {selector:selector(el), tag:el.tagName.toLowerCase(), text:(el.innerText||'').trim().slice(0,160), rect:{x:rect.x,y:rect.y,width:rect.width,height:rect.height}, style, animations};
  });
  const payload={url:location.href,viewport:{width:innerWidth,height:innerHeight,dpr:devicePixelRatio},colorScheme:matchMedia('(prefers-color-scheme: dark)').matches?'dark':'light',nodes};
  console.log(payload);
  copy(JSON.stringify(payload,null,2));
  return payload;
})();
