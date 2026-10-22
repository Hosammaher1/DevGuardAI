# DevGuard AI — Decision Register and Remaining Decisions

**Sources:** [PRD v4.3](../../product/PRD-v4.3.md), [master test plan](../tests/devguard-master-test-plan.md), [implementation plan](devguard-implementation-plan.md).

**Approval authority:** The product owner explicitly APPROVED ND01 Fixed Role Matrix and ND13 Selective Containment (option C). These approvals supersede the former unresolved status and tentative recommendations for those two decisions. They approve policy, not application implementation or executed validation. The other 13 ND entries below retain their original issues, options, recommendations and closure requirements as proposals. All linked cases remain **SPECIFIED / NOT EXECUTED**.

The numbering retains the preceding urgency order and each original ND ID. “R1 impact” means the affected task/acceptance gate; independent work need not wait. Approved policy and mechanisms still requiring technical validation are distinguished below.

1. **ND01 — APPROVED: Fixed Role Matrix**

   **Approved policy / PDs:** Exactly four workspace roles: Owner, Admin, Member, Viewer. The product-owner-approved 17-action matrix and RBAC-01–RBAC-15 below implement PD-010 and preserve PD-008/030 approval, PD-035 billing and all independent security controls.

   | Action | Owner | Admin | Member | Viewer |
   |---|---|---|---|---|
   | View authorized projects and reports | Yes | Yes | Yes | Yes |
   | Connect authorized repositories | Yes | Yes | No | No |
   | Start scans | Yes | Yes | Yes | No |
   | View authorized scan results | Yes | Yes | Yes | Yes |
   | Propose AI Fix plans | Yes | Yes | Yes | No |
   | Approve AI Fix execution | Yes | Yes | No | No |
   | Approve unverified Draft PR creation for review | Yes | Yes | No | No |
   | Cancel own jobs, subject to safety policy | Yes | Yes | Yes | No |
   | Manage project settings | Yes | Yes | No | No |
   | Invite members | Yes | Yes | No | No |
   | Remove Members or Viewers | Yes | Yes | No | No |
   | Change Member/Viewer roles | Yes | Yes | No | No |
   | Grant or revoke Admin role | Yes | No | No | No |
   | Manage billing and subscriptions | Yes | No | No | No |
   | Manage sensitive workspace settings | Yes | No | No | No |
   | Transfer workspace ownership | Yes | No | No | No |
   | Delete workspace | Yes | No | No | No |

   | Clause | Approved mandatory authorization policy |
   |---|---|
   | RBAC-01 | Default deny for unspecified actions. |
   | RBAC-02 | All authorization decisions must be enforced server-side. |
   | RBAC-03 | Every action must be scoped to an active workspace membership and authorized resource. |
   | RBAC-04 | A user cannot promote themselves or grant permissions beyond their own authority. |
   | RBAC-05 | An Admin cannot appoint another Admin or Owner. |
   | RBAC-06 | Membership invitations require acceptance by the intended identity. |
   | RBAC-07 | Expired, revoked, or already-used invitations must be rejected. |
   | RBAC-08 | Ownership transfer requires authorized confirmation and acceptance by the new owner. |
   | RBAC-09 | Never remove the last active Owner without a safe, completed ownership transfer. |
   | RBAC-10 | Membership removal or role reduction revokes future authorization immediately. |
   | RBAC-11 | Revalidate authorization before sensitive execution boundaries, including sandbox dispatch, AI Fix execution, GitHub writes and billing actions. |
   | RBAC-12 | Fix approvals must be bound to their precise workspace, repository, commit, file scope, fix plan and execution budget. |
   | RBAC-13 | Maintain auditable records of role changes, approvals and denied privileged actions without exposing secrets. |
   | RBAC-14 | Workspace roles never grant platform-level emergency Kill Switch authority. |
   | RBAC-15 | GitHub permissions, consent, sandbox policies and security gates remain independently enforced. |

   **R1 impact:** The former missing-policy blocker is resolved. T02 and all sensitive T08/T14/T15/T16 boundaries must implement the approved grants and current-policy checks; T19 stale-browser and later API authorization inherit them.

   **Options / disposition:** Owner-only privileged actions and customizable permissions were alternatives, not selected. The fixed delegated matrix is APPROVED. No fifth role, unspecified permission, self-escalation or workspace emergency authority is implied.

   **Recommendation / disposition:** Implement the exact approved matrix with server-side default denial, scoped active membership, accepted identity-bound invitations, completed ownership transfer, last-Owner protection, immediate revocation, precisely bound Fix approvals and protected audit. The earlier tentative recommendation is superseded by this binding policy.

   **Cases:** DG-IAM-002, DG-IAM-006, DG-IAM-008, DG-IAM-014, DG-IAM-015, DG-API-005, DG-BILL-001, DG-GATE-012, DG-IAM-017, DG-IAM-018, DG-IAM-019, DG-IAM-020, DG-IAM-021, DG-IAM-022, DG-JOB-012, DG-REC-013, DG-QA-009.

   **Closure:** Product-owner policy approval is complete. Technical/security validation remains for invitation token format/expiry, confirmation and acceptance mechanisms, role/ownership transaction consistency, sensitive-setting classification, immediate distributed revocation and audit durability. No implementation or successful execution is claimed; unspecified operations stay denied.

2. **ND13 — APPROVED: Selective Containment, option C**

   **Approved policy / PDs:** PD-039/SG-12, refined by PRD §3.5.1 KS-01–KS-10. Four independent platform controls: Scan, AI Fix, GitHub Write and Sandbox/Runner. Workspace roles do not authorize their operation.

   | Clause | Approved Selective Containment policy |
   |---|---|
   | KS-01 | Four independent platform-level controls: Scan, AI Fix, GitHub Write and Sandbox/Runner. A disabled dependency (for example a runner) can block dependent work without changing another switch's state. |
   | KS-02 | Activation and resumption require currently authorized platform operators. Workspace Owner/Admin/Member/Viewer roles confer no platform emergency authority. |
   | KS-03 | Prevent new affected side effects immediately at the trusted action boundary, including queued work, concurrent dispatch and retries. Already-submitted provider effects are handled by KS-06; activation is not a claim to undo a remote effect. |
   | KS-04 | Cancel, quarantine or isolate unsafe in-flight work according to incident scope; preserve truthful interrupted/partial/blocked state. The exact containment mechanism is a technical design choice, not permission to drain unsafe work. |
   | KS-05 | Continue safe trusted cleanup, auditing and reconciliation. Repository-defined cleanup hooks and arbitrary customer commands do not become trusted or exempt from isolation, authority or emergency policy. |
   | KS-06 | Reconcile already-submitted GitHub operations using observed provider state, including lost acknowledgments. Do not assume cancellation, duplicate a write, or bypass a disabled GitHub Write control with a reconciliation retry. |
   | KS-07 | Fail closed at dangerous execution boundaries when trusted switch state is unavailable. Only independently authorized safe trusted cleanup/audit/reconciliation may continue; unknown state never permits unsafe dispatch or resumption. |
   | KS-08 | Reconcile credit reservations idempotently under separate ND03 financial policy, using durable logical-job identities. ND13 approves no refund, cancellation charge, reset time or extra debit. |
   | KS-09 | Resume only after authorized incident review, verified remediation and current-policy revalidation of membership, resource authority, GitHub consent, precise Fix approval, quota, budget, capability and applicable switch state. Re-enabling one control does not re-enable another or replay unsafe work. |
   | KS-10 | Record activation, attempted unauthorized control, incident/scope, impacted jobs, containment, remote-effect resolution, credit reconciliation, recovery/resumption and operator identity with protected audit provenance; expose no secrets or cross-tenant data. |

   **R1 impact:** The former missing-policy blocker is resolved. T01 refines technical contracts; T06/T08/T09/T14/T15 enforce from their first affected paths, with all relevant independent controls rechecked. T18 owns integrated emergency verification, including DG-REC-011/012 and the targeted additional cases.

   **Options / disposition:** Immediate termination and unrestricted draining were not selected. Option C, Selective Containment, is APPROVED: stop new affected effects, contain unsafe active work, preserve safe trusted maintenance and reconcile observed external outcomes.

   **Recommendation / disposition:** Apply the approved policy exactly. Dangerous boundaries fail closed when trusted control state is unavailable; only authorized incident review, verified remediation and current-policy revalidation permit resumption. An already-submitted GitHub effect is reconciled rather than assumed cancelled; credit reservation reconciliation is idempotent under separate ND03.

   **Cases:** DG-REC-011, DG-REC-012, DG-REC-013, DG-REC-014, DG-REC-015, DG-REC-016, DG-REC-017, DG-REC-018, DG-REC-019, DG-REC-020, DG-REC-021, DG-REC-022, DG-REC-023, DG-REC-024, DG-QA-010.

   **Closure:** Product-owner policy approval is complete. Platform-operator provisioning/authentication, activation ordering/fencing, containment mechanisms, provider uncertain-effect reconciliation, protected audit persistence and safe recovery interfaces require technical validation. ND03 financial decisions remain unresolved; ND13 approves no refund arithmetic or automatic credit effect.

3. **ND15 — Operating geography, provider eligibility and privacy/legal validation**

   **Unresolved / PDs:** Eligible payment arrangements, billing/privacy geography, data regions, subprocessors and required provider/legal assurance. PD-037/038/039; related provider and billing requirements PD-025/035.

   **R1 impact:** Constrains T25 provider selection and T16 billing; blocks T22 public launch. Repeat for later providers.

   **Options:** A narrowly validated initial operating scope reduces review effort and available markets. Broader geographic coverage expands reach but increases provider, privacy and operational review.

   **Recommendation:** Define a limited initial operating scope, then obtain qualified eligibility/privacy/legal review before committing to providers. Record data flows, regions and subprocessors. This recommends a review sequence; it establishes no jurisdictional eligibility.

   **Cases:** DG-HOST-003, DG-GATE-009.

   **Closure:** Product-owner approval, appropriate legal/privacy review and technical validation. Actual fees and regional infrastructure costs feed viability decisions; measurements cannot replace eligibility review.

4. **ND05 — Artifact retention, access, encryption and credential lifecycle**

   **Unresolved / PDs:** Report-retention duration, signed-link expiry, encryption controls, separate financial/audit retention, and broker credential expiration/rotation/revocation. PD-018/027; related PD-012/028/035. Thirty retention days remains a proposal.

   **R1 impact:** Blocks detailed T03 artifact-policy and T23 credential-lifecycle acceptance. Depends on ND15 constraints.

   **Options:** Shorter retention reduces storage and retained exposure but limits historical usefulness. Longer retention improves investigation history at greater cost. Direct signed downloads simplify delivery; application-mediated access provides more control over authorization changes.

   **Recommendation:** Approve one paid-tier report duration after reviewing user needs and storage costs. Keep financial/audit retention separate. Prefer revocation-aware authorized delivery, short-lived links, managed encryption controls and brokered credential leases with defined rotation/revocation. Explicit credential lifecycle management aligns with [OWASP guidance](https://cheatsheetseries.owasp.org/cheatsheets/Secrets_Management_Cheat_Sheet.html).

   **Cases:** DG-SECRET-006, DG-REPORT-004, DG-REPORT-006, DG-REPORT-010, DG-REPORT-011, DG-REPORT-012, DG-REPORT-013.

   **Closure:** Product-owner/security/privacy approval; technical validation; real storage, retrieval and retention-cost measurements.

5. **ND03 — Credit settlement, cancellation, periods and reconciliation**

   **Unresolved / PDs:** Platform-error restoration, customer cancellation charging, reset/timezone rules, period transitions and reconciliation. DG-GH-008 additionally requires logical-run deduplication semantics. Primarily PD-016/017/035; related PD-003/015/019/030/036.

   **R1 impact:** Blocks financial boundary behavior in T04/T08/T15/T16, including crash and cancellation handling.

   **Options:** Calendar resets simplify shared dates; subscription-anchored periods align with individual billing cycles. Refunding every unsuccessful run is customer-friendly but absorbs customer-caused execution costs; distinguishing platform failure from customer outcomes needs clearer classification.

   **Recommendation:** Use subscription-anchored periods with UTC boundary instants. Release unstarted reservations; propose charging customer-caused failures/cancellations after a defined execution boundary and restoring confirmed platform failures once. Preserve the approved rule that executed unsuccessful Fix jobs count except platform-error handling. Separate transport retries from deliberately new scan requests.

   **Cases:** DG-GH-008, DG-JOB-011, DG-CREDIT-006, DG-CREDIT-009, DG-CREDIT-012, DG-CREDIT-013, DG-CREDIT-014, DG-CREDIT-015, DG-AI-013, DG-FIX-011, DG-BILL-004, DG-BILL-005, DG-BILL-013, DG-BILL-014, DG-GATE-012.

   **Closure:** Product-owner/finance approval and transactional/concurrency validation. Cost measurements inform refund economics but are not needed to specify idempotency.

6. **ND06 — Providers, images and integration contracts**

   **Unresolved / PDs:** Sandbox/cloud/macOS/payment/email/model/vault/queue choices, vetted images, least-privilege permissions and provider-specific contracts. PD-024/025/037; integration requirements PD-003/009/020 also apply.

   **R1 impact:** R1-required providers block T25/T05/T06/T16 integration. macOS/iOS evaluation belongs to T28; Desktop provider assurance belongs to T29 and cannot block R1.

   **Options:** Eligible providers with built-in required controls reduce integration work; candidates needing additional enforcement may offer flexibility but require more assurance and maintenance.

   **Recommendation:** Compare only candidates meeting the required security, geography and operational conditions. Start with one vetted R1 sandbox behind the approved provider-agnostic adapter. Select necessary service integrations individually; defer future native providers. Validate actual webhook and permission contracts rather than assuming common behavior.

   **Cases:** DG-GH-010, DG-GH-017, DG-RUN-011, DG-PLAT-010, DG-HOST-002, DG-HOST-003, DG-GATE-009.

   **Closure:** Product-owner approval of provider commitments, security/technical validation and real cost/performance measurements after security screening.

7. **ND07 — Supported versions and compatible runner profiles**

   **Unresolved / PDs:** Exact framework, browser, runtime, Android/iOS/Desktop versions and compatible execution profiles. PD-004/041/042.

   **R1 impact:** R1 discovery fixtures and Web execution profiles need decisions for T07/T10/T21. API/native profiles block only their corresponding later releases. R1 detection does not require operational native runners.

   **Options:** A narrow pinned matrix limits initial coverage but is easier to verify. A broad matrix serves more projects while multiplying compatibility and security evidence.

   **Recommendation:** Pin a small verified R1 matrix covering **both approved React/Vite and vanilla JavaScript scope**. Declare versions and Scan/Fix capabilities independently. Keep later execution profiles planned until separately validated.

   **Cases:** DG-DISC-008, DG-DISC-009, DG-API-005, DG-PLAT-002, DG-PLAT-003, DG-PLAT-004, DG-PLAT-005, DG-PLAT-006, DG-PLAT-008, DG-PLAT-009, DG-PLAT-010, DG-PLAT-011, DG-PLAT-012, DG-PLAT-013.

   **Closure:** Product-owner approval of support commitments and technical compatibility validation. Profile costs are measured through ND04 rather than constituting a separate prerequisite.

8. **ND04 — Resource, execution, network and retry bounds**

   **Unresolved / PDs:** CPU/RAM/disk/time/browser/model/token/concurrency/fair-use limits, fix retry maximum and concrete phase-network policies used by the marked cases. PD-013/016/017/026/031/033/038. Three retries remains a proposal.

   **R1 impact:** Blocks concrete T06 execution limits, T14/T15 bounded fixes and T24 cost-limit acceptance. Later composite/AI profiles require their own bounds.

   **Options:** Uniform limits simplify configuration but fit workloads unevenly. Capability-specific limits improve fit while adding configuration. Retry-count-only limits are simpler than combined attempt/time/spend limits but leave resource variation unresolved.

   **Recommendation:** Use versioned capability-specific ceilings with workspace concurrency limits and combined retry, time and spending bounds. Measure eligible ND06 providers on ND07 profiles before finalizing numbers. Specify phase-specific allowed destinations explicitly.

   **Cases:** DG-GH-016, DG-RUN-006, DG-RUN-013, DG-RUN-016, DG-RUN-017, DG-CREDIT-011, DG-CREDIT-012, DG-AI-011, DG-FIX-011, DG-FIX-015, DG-COST-004, DG-COST-006.

   **Closure:** Product-owner/security/finance approval, technical enforcement validation and real workload/tail-cost measurements.

9. **ND11 — Real-user trial eligibility and abuse controls**

   **Unresolved / PDs:** Practical definition of a qualifying real user, linked-identity handling and privacy-safe fraud prevention. PD-014/015.

   **R1 impact:** Blocks T16 trial eligibility and cross-workspace race acceptance. Depends on identity, privacy and ledger policy.

   **Options:** GitHub-account-only eligibility minimizes friction but cannot establish unique human identity. Additional risk signals and review improve discrimination while introducing privacy, false-positive and support costs.

   **Recommendation:** Use stable GitHub identity plus an atomic cross-workspace trial ledger, explicitly approved linked-identity evidence and proportionate risk review with an appeal path. Do not equate one GitHub account with one real person or introduce mandatory payment before the approved trial.

   **Cases:** DG-BILL-007, DG-BILL-008, DG-BILL-017.

   **Closure:** Product-owner/security/privacy approval and technical race/abuse validation. Measure actual trial cost, abuse and false-positive rates for commercial viability.

10. **ND12 — Recovery objectives and rollback procedures**

    **Unresolved / PDs:** Selected recovery-point/recovery-time objectives, backup/restore procedures and migration rollback design. PD-039, SG-11. One-hour RPO/four-hour RTO remains proposed.

    **R1 impact:** Blocks T18 recovery acceptance and R1 launch, rather than every earlier implementation task. Depends on provider, privacy, ledger and resumption policies.

    **Options:** Periodic backups are simpler but may permit larger recovery gaps. More frequent recovery checkpoints and stronger recovery infrastructure improve objectives at greater cost and operational effort.

    **Recommendation:** Select objectives from acceptable business loss and actual recovery capability. Require encrypted backups, recoverable checkpoints, rehearsed restoration and migration recovery, plus reconciliation of financial state and revoked authority before reopening service.

    **Cases:** DG-REC-007, DG-REC-008.

    **Closure:** Product-owner risk acceptance, security/operations technical validation, measured restore performance and actual backup/recovery costs.

11. **ND02 — Commercial values, upgrade arithmetic and margins**

    **Unresolved / PDs:** Prices, Team Scan cap, Fix allowances, upgrade quota/proration and margin thresholds. PD-014/015/017/035/038. Proposed values remain $19/$39/$99, Team 100 scans, Fix 5/15/40 and margins 60/40/20%; Basic 10 and Pro 30 monthly scans are already approved.

    **R1 impact:** Blocks exact T16 commercial behavior, T24 financial acceptance and public launch. Final values depend on ND03/04/05/06/11/12 measurements and policies.

    **Options:** Conservative allowances provide more cost headroom; generous allowances increase customer value and cost exposure. Upgrade grants could reset the pool, add the cap difference or prorate that difference; each changes economics and complexity.

    **Recommendation:** Finalize unapproved values from measured full costs, tails and stress scenarios. For upgrades, propose retaining used/reserved credits and the renewal date, granting only a disclosed prorated positive entitlement difference once after verified payment, with an explicit rounding rule.

    **Cases:** DG-BILL-005, DG-BILL-010, DG-BILL-011, DG-BILL-015, DG-COST-005, DG-GATE-009, DG-GATE-012.

    **Closure:** Product-owner/finance approval, technical billing validation and real cost measurements are all required.

12. **ND10 — Deployment completion and served-revision attribution**

    **Unresolved / PDs:** How to establish that deployment completed and which repository SHA the live target actually serves. PD-001/003.

    **R1 impact:** Blocks verified post-deployment SHA-attribution claims in T08. Ordinary authorized scans can retain an explicitly unknown live revision.

    **Options:** Provider completion events plus trusted served-revision evidence support stronger attribution but require integration. Human-confirmed mappings reduce integration effort but provide weaker evidence. Reporting “unknown” preserves honesty while limiting comparisons.

    **Recommendation:** Use authenticated deployment completion and trusted served-revision evidence where available. Otherwise retain unknown attribution and omit unsupported commit comparisons.

    **Cases:** DG-JOB-010.

    **Closure:** Product-owner approval of supported attribution behavior and technical validation. Real cost measurements are not a prerequisite.

13. **ND14 — Notification routing, preferences and delivery recovery**

    **Unresolved / PDs:** Critical/important/info definitions, permissible opt-outs, deduplication, retry and delivery policy. PD-011/034; recipient authorization also relates to PD-010.

    **R1 impact:** Blocks detailed T20 routing/recovery acceptance and commercial completeness; it need not block the first scan slice.

    **Options:** Immediate actionable notifications minimize delay but increase volume. Immediate critical alerts with important-event digests reduce noise but delay some information.

    **Recommendation:** Propose immediate critical notifications, important-event digests unless time-sensitive, and informational Dashboard updates. Define approved preference exceptions. Use stable notification identities, bounded retries and delivery-time permission checks; specify what happens after an uncertain provider acknowledgment without repeating job or financial effects.

    **Cases:** DG-NOTIFY-002, DG-NOTIFY-005, DG-NOTIFY-007.

    **Closure:** Product-owner/operations approval and technical channel validation. Measure delivery volume/cost for budgeting; routing approval need not await those measurements.

14. **ND09 — Supplementary accessibility and UI limits**

    **Unresolved / PDs:** Accessibility/assistive-technology matrix, UI boundary limits and any conformance claim. PD-029 supplies context only; the PRD approves no accessibility standard.

    **R1 impact:** Blocks acceptance of supplementary DG-UX-004, not an existing mandatory R1 release gate.

    **Options:** A journey-specific keyboard/screen-reader matrix is narrower and faster to review. A formal WCAG target provides broader criteria and requires more assessment.

    **Recommendation:** Propose [WCAG 2.2 AA](https://www.w3.org/TR/WCAG22/) as an internal target for approval, with explicit keyboard/assistive-technology journeys. Define display/input limits separately. Do not declare conformance or add a launch gate through this recommendation.

    **Cases:** DG-UX-004 explicitly. DG-UX-005 is related to UI boundary handling but currently carries no ND09 marker.

    **Closure:** Product-owner/design approval and technical/manual accessibility validation. Real cost measurements are not a prerequisite.

15. **ND08 — AI Applications evaluation oracles**

    **Unresolved / PDs:** Versioned corpora, rubrics, metrics, thresholds and aggregation of nondeterministic observations. PD-041; related PD-038/042.

    **R1 impact:** **R3 only:** blocks T26 evaluation acceptance. It does not block the R1 risk-based test planner.

    **Options:** Small curated task-specific corpora provide clearer expected outcomes but narrower coverage. Larger sanitized representative corpora broaden coverage while increasing labeling, privacy and evaluation costs.

    **Recommendation:** Start with versioned task-specific corpora, deterministic tool/contract assertions and human-reviewed rubrics. Define repeated-observation aggregation and uncertainty explicitly; expand representative coverage before making broader quality claims.

    **Cases:** DG-AI-002, DG-AI-003, DG-AI-006, DG-AI-008, DG-AI-010, DG-AI-013.

    **Closure:** Product-owner/QA approval, technical evaluation validation and real token/runtime/corpus-maintenance cost measurements before R3 availability.

## Unverified Runtime QA technical records

The requested Runtime QA design adds QTD-01 manifest/schema/version/integrity/compatibility/rollback mechanisms, QTD-02 tool/adapter/error/cancellation contracts, QTD-03 per-capability labeled/holdout benchmarks and FP/FN/regression/promotion thresholds, and QTD-04 private evidence/confidence/reproducibility formats. These records require technical/QA validation before the affected production capability is admitted. Exact budgets, provider/version choices and existing financial/privacy policies remain governed by their original unresolved ND entries.

QTD-03 R1 runtime-skill quality benchmarks are separate from ND08's R3 customer-AI evaluation corpus. Basic Runtime QA accessibility diagnostics do not resolve ND09 or create an unapproved conformance claim. Future runtime capability/provider work does not block R1.

No application code, tests, installations, Git configuration changes, push, cloud resources or deployment are authorized by these documents. The implementation plan records the read-only documentation verification report; no skill or security gate is claimed behaviorally verified by document consistency checks.
