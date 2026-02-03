// top main hero section animation using GSAP
gsap.from('.hero_title', {
    y: -50,
    opacity: 0,
    duration: 1,
    delay: 1
});

gsap.from('.hero_description', {
    x: 100,
    opacity: 0,
    duration: 1,
    delay: 1.5
});

gsap.from('.hero_main_img', {
    x: 100,
    opacity: 0,
    duration: 1.5,
    delay: 2
});
gsap.from('#hero_get_started_btn', {
    y: 20,
    opacity: 0,
    duration: 0.5,
    delay: 2.5
});
gsap.from('#hero_book_demo_btn', {
    y: 20,
    opacity: 0,
    duration: 0.5,
    delay: 2.7
});

// top main cards section animation using GSAP
gsap.from('.hero_cards .hero_card_item', {
    opacity: 0,
    duration: 1.5,
    delay: 2.9,
    stagger: {
        amount: 1.5,
        from: "start"
    }
});

gsap.from('.features_section .features_item', {
    scrollTrigger: {
        trigger: '.features_section',  
        start: "top 100%",
        end: "bottom 50%",
        scrub: true,
        markers: false,
    },
    opacity: 0,
    duration: 1.5,  
    stagger: {
        amount: 1.5,
        from: "start"
    }
});

gsap.from('.features_section .item_left_1 .features_img_left', {
    scrollTrigger: {
        trigger: '.features_item',  
        start: "top 100%",
        end: "bottom 50%",
        scrub: true,
    },
    x: -200,
    opacity: 0,
    duration: 1.5,  
    delay: 1,
});
gsap.from('.features_section .item_left_2 .features_img_left', {
    scrollTrigger: {
        trigger: '.item_left_2',  
        start: "top 100%",
        end: "bottom 50%",
        scrub: true,
    },
    x: -200,
    opacity: 0,
    duration: 1.5,  
    delay: 1,
});
gsap.from('.features_section .item_right .features_img_right', {
    scrollTrigger: {
        trigger: '.item_right',  
        start: "top 100%",
        end: "bottom 50%",
        scrub: true,
    },
    x: 200,
    opacity: 0,
    duration: 1.5,  
    delay: 1,
});


gsap.from('.book_demo_section', {
    scrollTrigger: {
        trigger: '.book_demo_section',  
        start: "top 100%",
        end: "bottom 80%",
        scrub: true,
        markers: false,
    },
    opacity: 0,
    duration: 2,  
    delay: 2
});




