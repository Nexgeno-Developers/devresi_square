<header class="pf-top">
    <div class="pf-brand"><span class="pf-mark"></span> Resisquare</div>
    <div class="pf-who">
        <span class="pf-avatar">LL</span>
        <span>
            <strong>Lara Landlord</strong>
            <em>Landlord Basic · 2 of 5 homes</em>
        </span>
    </div>
</header>

<main class="pf">
    <header class="pf-hello">
        <div>
            <p class="pf-kicker">Wednesday 16 Sep</p>
            <h1>Good afternoon, Lara</h1>
            <p>Two homes. One let. £1,100 still to collect.</p>
        </div>
        <button type="button" class="lab-btn lab-btn-primary" data-lab-panel="add-home">Add a home</button>
    </header>

    <section class="pf-needs" aria-label="Needs you">
        <h2>Needs you</h2>
        <button type="button" class="pf-need" data-lab-home="wharf" data-lab-panel="pay">
            <span>September rent · Tina · Flat 108</span>
            <strong>£1,100.00 unpaid</strong>
        </button>
        <button type="button" class="pf-need" data-lab-home="wharf" data-lab-panel="repair">
            <span>Kitchen tap dripping · Flat 108</span>
            <strong>Pending since 14 Sep</strong>
        </button>
        <button type="button" class="pf-need" data-lab-home="wharf" data-lab-panel="certs">
            <span>Gas Safe · Flat 108</span>
            <strong>No certificate on file</strong>
        </button>
    </section>

    <section class="pf-homes" aria-label="Your homes">
        <article class="pf-home is-open" id="home-wharf" data-home="wharf">
            <img src="https://images.unsplash.com/photo-1502672260266-1c1ef2d93688?auto=format&fit=crop&w=1400&q=70" alt="">
            <div class="pf-home-body">
                <div class="pf-home-head">
                    <span class="lab-pill lab-pill-ok">Let</span>
                    <span class="pf-meta">2 bed · Flat</span>
                </div>
                <h2>Flat 108</h2>
                <p class="pf-addr">1 Baltimore Wharf · E14 9RU</p>

                <dl class="pf-facts">
                    <div>
                        <dt>Household</dt>
                        <dd>Tina Tenant <span class="pf-quiet">can sign in</span></dd>
                    </div>
                    <div>
                        <dt>Rent</dt>
                        <dd>£1,250 / month <span class="lab-pill lab-pill-warn">£1,100 unpaid</span></dd>
                    </div>
                    <div>
                        <dt>Repair</dt>
                        <dd>Kitchen tap dripping <span class="pf-quiet">pending</span></dd>
                    </div>
                    <div>
                        <dt>Certificates</dt>
                        <dd>EPC B <span class="lab-pill lab-pill-warn">Gas Safe missing</span></dd>
                    </div>
                </dl>

                <div class="pf-work" id="work-wharf">
                    <div class="pf-tabs" role="tablist">
                        <button type="button" class="is-on" data-lab-tab="pay" data-home="wharf">Rent</button>
                        <button type="button" data-lab-tab="repair" data-home="wharf">Repair</button>
                        <button type="button" data-lab-tab="certs" data-home="wharf">Certificates</button>
                        <button type="button" data-lab-tab="people" data-home="wharf">People</button>
                    </div>

                    <div class="pf-panel is-on" data-panel="pay" data-home="wharf">
                        <div class="pf-row" data-invoice>
                            <div>
                                <strong>September 2026</strong>
                                <span>Due 28 Sep · Tina Tenant</span>
                            </div>
                            <span class="lab-pill lab-pill-warn" data-invoice-status>Unpaid</span>
                            <strong data-invoice-amount>£1,100.00</strong>
                        </div>
                        <div class="pf-row">
                            <div>
                                <strong>August 2026</strong>
                                <span>Paid 14 Aug</span>
                            </div>
                            <span class="lab-pill lab-pill-ok">Paid</span>
                            <strong>£1,250.00</strong>
                        </div>
                        <form class="pf-form" data-lab-pay>
                            <label>Received <input type="text" value="£1,100.00"></label>
                            <label>On <input type="text" value="16 Sep 2026"></label>
                            <label>How
                                <select>
                                    <option>Bank transfer</option>
                                    <option>Card</option>
                                    <option>Cash</option>
                                </select>
                            </label>
                            <button type="submit" class="lab-btn lab-btn-primary">Record payment</button>
                        </form>
                    </div>

                    <div class="pf-panel" data-panel="repair" data-home="wharf" hidden>
                        <p class="pf-repair-copy">Hot tap in the kitchen drips after use. Tina reported it on 14 Sep. Priority normal. No contractor booked.</p>
                        <form class="pf-form" data-lab-repair>
                            <label>Status
                                <select>
                                    <option>Pending</option>
                                    <option>Under way</option>
                                    <option>Closed</option>
                                </select>
                            </label>
                            <label class="pf-grow">Note <input type="text" placeholder="Booked plumber for Thursday"></label>
                            <button type="submit" class="lab-btn lab-btn-primary">Update repair</button>
                        </form>
                    </div>

                    <div class="pf-panel" data-panel="certs" data-home="wharf" hidden>
                        <div class="pf-certs">
                            <div>
                                <strong>EPC</strong>
                                <span class="lab-pill lab-pill-ok">B</span>
                                <p>On the register. Store a PDF only if you need to share it.</p>
                            </div>
                            <div>
                                <strong>Gas Safe</strong>
                                <span class="lab-pill lab-pill-warn">Missing</span>
                                <p>This let has gas. Annual certificate is still empty.</p>
                                <button type="button" class="lab-btn lab-btn-secondary">Upload PDF</button>
                            </div>
                            <div>
                                <strong>EICR</strong>
                                <span class="lab-pill lab-pill-warn">Missing</span>
                                <p>Electrical safety, usually every five years.</p>
                                <button type="button" class="lab-btn lab-btn-secondary">Upload PDF</button>
                            </div>
                        </div>
                    </div>

                    <div class="pf-panel" data-panel="people" data-home="wharf" hidden>
                        <div class="pf-row">
                            <div>
                                <strong>Tina Tenant</strong>
                                <span>tenant@resisquare.test · main tenant</span>
                            </div>
                            <span class="lab-pill lab-pill-ok">Can sign in</span>
                        </div>
                        <div class="pf-row">
                            <div>
                                <strong>Lara Landlord</strong>
                                <span>Owner · 100%</span>
                            </div>
                            <span class="lab-pill lab-pill-idle">You</span>
                        </div>
                    </div>
                </div>
            </div>
        </article>

        <article class="pf-home" id="home-street" data-home="street">
            <img src="https://images.unsplash.com/photo-1568605114967-8130f3a36994?auto=format&fit=crop&w=1400&q=70" alt="">
            <div class="pf-home-body">
                <div class="pf-home-head">
                    <span class="lab-pill lab-pill-idle">Vacant</span>
                    <span class="pf-meta">2 bed · House</span>
                </div>
                <h2>1 Staging Landlord Street</h2>
                <p class="pf-addr">London · ST1 1AA</p>

                <dl class="pf-facts">
                    <div>
                        <dt>Household</dt>
                        <dd>None <span class="pf-quiet">no active tenancy</span></dd>
                    </div>
                    <div>
                        <dt>Asking</dt>
                        <dd>£1,250 / month</dd>
                    </div>
                    <div>
                        <dt>Repair</dt>
                        <dd>None open</dd>
                    </div>
                    <div>
                        <dt>Certificates</dt>
                        <dd>None recorded</dd>
                    </div>
                </dl>

                <div class="pf-work" id="work-street">
                    <div class="pf-tabs" role="tablist">
                        <button type="button" class="is-on" data-lab-tab="let" data-home="street">Let this home</button>
                    </div>
                    <div class="pf-panel is-on" data-panel="let" data-home="street">
                        <form class="pf-form" data-lab-let>
                            <label>Tenant <input type="text" placeholder="Full name"></label>
                            <label>Email <input type="email" placeholder="name@email.com"></label>
                            <label>Move in <input type="text" value="1 Oct 2026"></label>
                            <label>Rent <input type="text" value="£1,250.00"></label>
                            <button type="submit" class="lab-btn lab-btn-primary">Save tenancy</button>
                        </form>
                    </div>
                </div>
            </div>
        </article>
    </section>

    <section class="pf-add" id="panel-add-home" hidden>
        <h2>Add a home</h2>
        <p>Search by postcode. We’ll fill the rest where we can.</p>
        <form class="pf-form">
            <label>Postcode <input type="text" value="E14 9RU"></label>
            <button type="button" class="lab-btn lab-btn-primary">Search</button>
            <button type="button" class="lab-btn lab-btn-secondary" data-lab-close-panel>Cancel</button>
        </form>
    </section>
</main>
