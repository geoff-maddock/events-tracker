@extends('layouts.app-tw')

@section('title', 'Terms of Service')

@section('content')
<div class="max-w-4xl mx-auto">
    <h1 class="text-3xl font-bold text-foreground mb-8">{{ config('app.app_name') }} - Terms of Service</h1>

    <div class="grid md:grid-cols-4 gap-6">
        <!-- Table of Contents Sidebar -->
        <nav class="md:col-span-1">
            <div class="card-tw p-4 sticky top-4">
                <h2 class="font-semibold text-foreground mb-3">Table of Contents</h2>
                <ul class="space-y-2 text-sm">
                    <li><a href="#toc0" class="text-muted-foreground hover:text-primary transition-colors">Legal Notices</a></li>
                    <li><a href="#toc1" class="text-muted-foreground hover:text-primary transition-colors">Permitted and Prohibited Uses</a></li>
                    <li><a href="#toc2" class="text-muted-foreground hover:text-primary transition-colors">User Submissions</a></li>
                    <li><a href="#toc3" class="text-muted-foreground hover:text-primary transition-colors">User Discussion Lists and Forums</a></li>
                    <li><a href="#toc4" class="text-muted-foreground hover:text-primary transition-colors">Use of Personally Identifiable Information</a></li>
                    <li><a href="#toc5" class="text-muted-foreground hover:text-primary transition-colors">Indemnification</a></li>
                    <li><a href="#toc6" class="text-muted-foreground hover:text-primary transition-colors">Termination</a></li>
                    <li><a href="#toc7" class="text-muted-foreground hover:text-primary transition-colors">Warranty Disclaimer</a></li>
                    <li><a href="#toc8" class="text-muted-foreground hover:text-primary transition-colors">General</a></li>
                    <li><a href="#toc9" class="text-muted-foreground hover:text-primary transition-colors">Links to Other Materials</a></li>
                    <li><a href="#toc10" class="text-muted-foreground hover:text-primary transition-colors">Copyright Infringement</a></li>
                </ul>
            </div>
        </nav>

        <!-- Content -->
        <div class="md:col-span-3 card-tw p-6">
            <div class="prose prose-sm dark:prose-invert max-w-none space-y-8">
                <section id="toc0">
                    <h2 class="text-xl font-semibold text-foreground border-b border-border pb-2 mb-4">Legal Notices</h2>
                    <div class="space-y-3 text-muted-foreground">
                        <p>We, the Operators of this Website, provide it as a public service to our users.</p>
                        <p>Please carefully review the following basic rules that govern your use of the Website. Please note that your use of the Website constitutes your unconditional agreement to follow and be bound by these Terms and Conditions of Use. If you (the "User") do not agree to them, do not use the Website, provide any materials to the Website or download any materials from them.</p>
                        <p>The Operators reserve the right to update or modify these Terms and Conditions at any time without prior notice to User. Your use of the Website following any such change constitutes your unconditional agreement to follow and be bound by these Terms and Conditions as changed. For this reason, we encourage you to review these Terms and Conditions of Use whenever you use the Website.</p>
                        <p>These Terms and Conditions of Use apply to the use of the Website and do not extend to any linked third party sites. These Terms and Conditions and our <a href="/privacy" class="text-primary hover:underline">Privacy Policy</a>, which are hereby incorporated by reference, contain the entire agreement (the "Agreement") between you and the Operators with respect to the Website. Any rights not expressly granted herein are reserved.</p>
                    </div>
                </section>

                <section id="toc1">
                    <h2 class="text-xl font-semibold text-foreground border-b border-border pb-2 mb-4">Permitted and Prohibited Uses</h2>
                    <div class="space-y-3 text-muted-foreground">
                        <p>You may use the Website for the sole purpose of sharing and exchanging ideas with other Users. You may not use the Website to violate any applicable local, state, national, or international law, including without limitation any applicable laws relating to antitrust or other illegal trade or business practices, federal and state securities laws, regulations promulgated by the U.S. Securities and Exchange Commission, any rules of any national or other securities exchange, and any U.S. laws, rules, and regulations governing the export and re-export of commodities or technical data.</p>
                        <p>You may not upload or transmit any material that infringes or misappropriates any person's copyright, patent, trademark, or trade secret, or disclose via the Website any information the disclosure of which would constitute a violation of any confidentiality obligations you may have.</p>
                        <p>You may not upload any viruses, worms, Trojan horses, or other forms of harmful computer code, nor subject the Website's network or servers to unreasonable traffic loads, or otherwise engage in conduct deemed disruptive to the ordinary operation of the Website.</p>
                        <p>You are strictly prohibited from communicating on or through the Website any unlawful, harmful, offensive, threatening, abusive, libelous, harassing, defamatory, vulgar, obscene, profane, hateful, fraudulent, sexually explicit, racially, ethnically, or otherwise objectionable material of any sort.</p>
                        <p>You are expressly prohibited from compiling and using other Users' personal information for the purpose of creating or compiling marketing and/or mailing lists and from sending other Users unsolicited marketing materials.</p>
                    </div>
                </section>

                <section id="toc2">
                    <h2 class="text-xl font-semibold text-foreground border-b border-border pb-2 mb-4">User Submissions</h2>
                    <div class="space-y-3 text-muted-foreground">
                        <p>The Operators do not want to receive confidential or proprietary information from you through the Website. Any material, information, or other communication you transmit or post ("Contributions") to the Website will be considered non-confidential.</p>
                        <p>All contributions to this site are licensed by you under the MIT License to anyone who wishes to use them, including the Operators.</p>
                        <p>If you work for a company or at a University, it's likely that you're not the copyright holder of anything you make, even in your free time. Before making contributions to this site, get written permission from your employer.</p>
                    </div>
                </section>

                <section id="toc3">
                    <h2 class="text-xl font-semibold text-foreground border-b border-border pb-2 mb-4">User Discussion Lists and Forums</h2>
                    <div class="space-y-3 text-muted-foreground">
                        <p>The Operators may, but are not obligated to, monitor or review any areas on the Website where users transmit or post communications or communicate solely with each other, including but not limited to user forums and email lists, and the content of any such communications. The Operators, however, will have no liability related to the content of any such communications, whether or not arising under the laws of copyright, libel, privacy, obscenity, or otherwise. The Operators may edit or remove content on the Website at their discretion at any time.</p>
                    </div>
                </section>

                <section id="toc4">
                    <h2 class="text-xl font-semibold text-foreground border-b border-border pb-2 mb-4">Use of Personally Identifiable Information</h2>
                    <div class="space-y-3 text-muted-foreground">
                        <p>Information submitted to the Website is governed according to the Operators's current <a href="/privacy" class="text-primary hover:underline">Privacy Policy</a> and the stated license of this website.</p>
                        <p>You agree to provide true, accurate, current, and complete information when registering with the Website. It is your responsibility to maintain and promptly update this account information to keep it true, accurate, current, and complete.</p>
                    </div>
                </section>

                <section id="toc5">
                    <h2 class="text-xl font-semibold text-foreground border-b border-border pb-2 mb-4">Indemnification</h2>
                    <div class="space-y-3 text-muted-foreground">
                        <p>You agree to defend, indemnify and hold harmless the Operators, agents, vendors or suppliers from and against any and all claims, damages, costs and expenses, including reasonable attorneys' fees, arising from or related to your use or misuse of the Website, including, without limitation, your violation of these Terms and Conditions, the infringement by you, or any other subscriber or user of your account, of any intellectual property right or other right of any person or entity.</p>
                    </div>
                </section>

                <section id="toc6">
                    <h2 class="text-xl font-semibold text-foreground border-b border-border pb-2 mb-4">Termination</h2>
                    <div class="space-y-3 text-muted-foreground">
                        <p>These Terms and Conditions of Use are effective until terminated by either party. If you no longer agree to be bound by these Terms and Conditions, you must cease use of the Website. If you are dissatisfied with the Website, their content, or any of these terms, conditions, and policies, your sole legal remedy is to discontinue using the Website. The Operators reserve the right to terminate or suspend your access to and use of the Website, or parts of the Website, without notice.</p>
                    </div>
                </section>

                <section id="toc7">
                    <h2 class="text-xl font-semibold text-foreground border-b border-border pb-2 mb-4">Warranty Disclaimer</h2>
                    <div class="space-y-3 text-muted-foreground bg-muted/50 p-4 rounded-lg text-sm">
                        <p>THE WEBSITE AND ASSOCIATED MATERIALS ARE PROVIDED ON AN "AS IS" AND "AS AVAILABLE" BASIS. TO THE FULL EXTENT PERMISSIBLE BY APPLICABLE LAW, THE OPERATORS DISCLAIM ALL WARRANTIES, EXPRESS OR IMPLIED, INCLUDING, BUT NOT LIMITED TO, IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE, OR NON-INFRINGEMENT OF INTELLECTUAL PROPERTY.</p>
                        <p>IN NO EVENT SHALL THE OPERATORS OR ANY OF THEIR AGENTS, VENDORS OR SUPPLIERS BE LIABLE FOR ANY DAMAGES WHATSOEVER ARISING OUT OF THE USE, MISUSE OF OR INABILITY TO USE THE WEBSITE.</p>
                        <p>THE OPERATORS'S TOTAL CUMULATIVE LIABILITY FOR ANY AND ALL CLAIMS IN CONNECTION WITH THE WEBSITE WILL NOT EXCEED FIVE U.S. DOLLARS ($5.00).</p>
                    </div>
                </section>

                <section id="toc8">
                    <h2 class="text-xl font-semibold text-foreground border-b border-border pb-2 mb-4">General</h2>
                    <div class="space-y-3 text-muted-foreground">
                        <p>The Website is hosted in the United States. The Operators make no claims that the Content on the Website is appropriate or may be downloaded outside of the United States. Access to the Content may not be legal by certain persons or in certain countries. If you access the Website from outside the United States, you do so at your own risk and are responsible for compliance with the laws of your jurisdiction.</p>
                    </div>
                </section>

                <section id="toc9">
                    <h2 class="text-xl font-semibold text-foreground border-b border-border pb-2 mb-4">Links to Other Materials</h2>
                    <div class="space-y-3 text-muted-foreground">
                        <p>The Website may contain links to sites owned or operated by independent third parties. These links are provided for your convenience and reference only. We do not control such sites and, therefore, we are not responsible for any content posted on these sites. The fact that the Operators offer such links should not be construed in any way as an endorsement, authorization, or sponsorship of that site.</p>
                    </div>
                </section>

                <section id="toc10">
                    <h2 class="text-xl font-semibold text-foreground border-b border-border pb-2 mb-4">Notification Of Possible Copyright Infringement</h2>
                    <div class="space-y-3 text-muted-foreground">
                        <p>In the event you believe that material or content published on the Website may infringe on your copyright or that of another, please <a href="mailto:{{ config('app.admin') }}" class="text-primary hover:underline">contact us</a>.</p>
                    </div>
                </section>
            </div>
        </div>
    </div>
</div>
@stop
