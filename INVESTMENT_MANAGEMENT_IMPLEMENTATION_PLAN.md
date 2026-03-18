# Investment Management Module & ECL Engine - Implementation Plan

## Executive Summary

This document outlines the phased implementation plan for the **Investment Management Module** and **ECL (Expected Credit Loss) Engine** in SmartAccounting. The implementation is broken down into 5 phases, each building upon the previous phase to deliver incremental value while maintaining system stability.

**Total Estimated Timeline**: 16-20 weeks (4-5 months)
**Team Size**: 2-3 developers + 1 QA + 1 Business Analyst

---

## Understanding & Architecture Overview

### Current System Capabilities
- ✅ Journal/GL posting system with approval workflows
- ✅ Multi-level approval system (role-based)
- ✅ Period locking for period closing
- ✅ Scheduled jobs and commands infrastructure
- ✅ RBAC using Spatie permissions
- ✅ Service layer pattern established
- ✅ Audit logging (LogsActivity trait)

### New Module Requirements
1. **Investment Lifecycle Management**
   - Purchase → Settlement → Amortization → Valuation → Disposal
   - Support for multiple instrument types (T-Bills, Bonds, Fixed Deposits, Equity, etc.)
   - IFRS 9 accounting classifications (Amortized Cost, FVOCI, FVPL)

2. **EIR (Effective Interest Rate) Calculation**
   - Numerical solver for complex cash flow structures
   - Amortization schedule generation
   - Periodic interest accrual

3. **Valuation Engine**
   - Level 1 (quoted prices)
   - Level 2 (observable inputs)
   - Level 3 (model-based)

4. **ECL Engine**
   - IFRS 9 3-stage model
   - Simplified approach for trade receivables
   - Scenario-based forward-looking adjustments

5. **Integration Points**
   - GL posting service
   - Bank/RTGS integration
   - Market data feeds
   - Tax module
   - Approval workflows

---

## Phase 1: Foundation & Core Data Model (Weeks 1-4)

### Objectives
- Establish database schema
- Create core models and relationships
- Build basic CRUD operations
- Implement proposal and approval workflow

### Deliverables

#### 1.1 Database Migrations
- `investment_master` table
- `investment_trade` table
- `investment_proposals` table
- `investment_approvals` table
- `investment_attachments` table
- Basic audit tables

#### 1.2 Models & Relationships
- `InvestmentMaster` model
- `InvestmentTrade` model
- `InvestmentProposal` model
- `InvestmentApproval` model
- Relationships: Company, Branch, User, ChartAccount

#### 1.3 Basic Services
- `InvestmentProposalService` - Create, update, submit proposals
- `InvestmentApprovalService` - Handle approval workflow
- `InvestmentMasterService` - Basic CRUD operations

#### 1.4 Controllers & Routes
- `InvestmentProposalController` - Proposal management
- `InvestmentMasterController` - Investment master data
- API routes with RBAC protection

#### 1.5 Frontend Screens
- Investment Proposal Form
- Proposal List/Grid
- Approval Screen
- Basic Investment Master List

#### 1.6 Integration
- Reuse existing approval workflow infrastructure
- Integrate with existing RBAC system
- Attachment storage (reuse existing file storage)

### Acceptance Criteria
- ✅ Can create investment proposals
- ✅ Multi-level approval workflow works
- ✅ Proposals can be approved/rejected with comments
- ✅ Approved proposals can be converted to investment master
- ✅ All actions are audit-logged
- ✅ RBAC permissions enforced

### Technical Notes
- Use existing `JournalEntryApproval` pattern for approvals
- Leverage `LogsActivity` trait for audit trails
- Follow existing service layer patterns

---

## Phase 2: Trade Capture & Initial Recognition (Weeks 5-8)

### Objectives
- Implement trade capture (deal ticket)
- Settlement workflow
- Initial GL posting for purchases
- Basic portfolio view

### Deliverables

#### 2.1 Trade Capture Service
- `InvestmentTradeService` - Trade creation, validation
- Trade validation rules (settlement date >= trade date, etc.)
- Settlement instruction generation

#### 2.2 Settlement Integration
- Settlement workflow
- Bank payment integration hooks
- Settlement status tracking

#### 2.3 GL Posting Service
- `InvestmentJournalService` - Generate journal entries
- Initial recognition journal (Purchase)
- Integration with existing `GLTransactionService`
- Journal preview before posting

#### 2.4 Controllers
- `InvestmentTradeController` - Trade capture endpoints
- Settlement endpoints

#### 2.5 Frontend Screens
- Trade Capture Form (Deal Ticket)
- Settlement Instruction Screen
- Portfolio Summary Dashboard (basic)

#### 2.6 Database Extensions
- Add settlement status fields
- Add bank reference tracking
- Link trades to journals

### Acceptance Criteria
- ✅ Can capture purchase trades
- ✅ Settlement workflow works
- ✅ Initial recognition journal posts correctly
- ✅ Journal entries balance (debit = credit)
- ✅ Portfolio summary shows investments
- ✅ Can filter by instrument type, status

### Technical Notes
- Reuse `Journal` and `JournalItem` models
- Integrate with existing period locking
- Use existing chart account structure

---

## Phase 3: EIR Calculation & Amortization (Weeks 9-12)

### Objectives
- Implement EIR solver algorithm
- Generate amortization schedules
- Periodic interest accrual
- Coupon payment handling

### Deliverables

#### 3.1 EIR Calculation Engine
- `EirCalculatorService` - Numerical solver (Newton-Raphson or bisection)
- Handle various cash flow patterns (annuity, bullet, irregular)
- Day count conventions (ACT/365, ACT/360, 30/360)

#### 3.2 Amortization Service
- `InvestmentAmortizationService` - Generate amortization lines
- Store in `investment_amort_line` table
- Recompute EIR when needed

#### 3.3 Accrual Service
- `InvestmentAccrualService` - Periodic interest accrual
- Monthly/daily accrual calculation
- Accrual journal generation

#### 3.4 Coupon Payment Service
- Handle coupon payments
- Link to trades
- Update carrying amounts

#### 3.5 Scheduled Jobs
- Monthly EIR amortization job
- Daily/weekly accrual job (configurable)
- Idempotency handling

#### 3.6 Frontend Screens
- Amortization Schedule Viewer
- EIR Calculator/Recompute UI
- Accrual Posting Screen
- Coupon Payment Screen

#### 3.7 Database
- `investment_amort_line` table
- EIR calculation audit fields

### Acceptance Criteria
- ✅ EIR calculated correctly for standard instruments
- ✅ Amortization schedule generated accurately
- ✅ Accrual journals post correctly
- ✅ Coupon payments update carrying amounts
- ✅ Scheduled jobs run without double-posting
- ✅ Can recompute EIR when cash flows change

### Technical Notes
- Use high-precision decimal calculations (BCMath or Decimal)
- Implement caching for EIR calculations
- Store calculation inputs for audit

---

## Phase 4: Valuation & Revaluation (Weeks 13-15)

### Objectives
- Implement fair value valuation
- Support Level 1, 2, 3 valuations
- Revaluation journal posting
- FVOCI and FVPL accounting

### Deliverables

#### 4.1 Valuation Service
- `InvestmentValuationService` - Calculate fair values
- Level 1: Market price × units
- Level 2: Yield curve discounting (basic)
- Level 3: DCF model (basic structure)

#### 4.2 Revaluation Service
- `InvestmentRevaluationService` - Compute deltas
- Generate revaluation journals
- Handle FVOCI reserve vs P&L

#### 4.3 Market Data Integration
- Market price import (CSV/manual)
- Price feed structure (for future API integration)
- Price history tracking

#### 4.4 Scheduled Jobs
- Daily valuation job (for FVPL/FVOCI)
- Price import job

#### 4.5 Frontend Screens
- Valuation Entry Screen
- Revaluation Preview & Approval
- Fair Value Hierarchy Report
- Valuation History

#### 4.6 Database
- `investment_valuation` table
- Market price history table

### Acceptance Criteria
- ✅ Can enter/manual valuations
- ✅ Revaluation journals post correctly
- ✅ FVOCI reserve tracked separately
- ✅ FVPL gains/losses go to P&L
- ✅ Valuation history maintained
- ✅ Can export valuation inputs

### Technical Notes
- Separate FVOCI reserve account in chart of accounts
- Store valuation methodology and inputs
- Approval required for Level 3 valuations

---

## Phase 5: ECL Engine & Advanced Features (Weeks 16-20)

### Objectives
- Implement IFRS 9 ECL calculation
- Simplified approach for receivables
- Staging rules (SICR detection)
- Scenario-based calculations
- Deferred tax integration
- Comprehensive reporting

### Deliverables

#### 5.1 ECL Data Model
- `ecl_model_params` table
- `ecl_scenarios` table
- `ecl_inputs` table
- `ecl_calc` table
- `ecl_override` table

#### 5.2 ECL Calculation Engine
- `EclCalculationService` - Core ECL logic
- Simplified approach (provision matrix)
- General approach (12-month and lifetime)
- Scenario weighting

#### 5.3 Staging Service
- `EclStagingService` - SICR detection
- Days past due rules
- PD increase rules
- Stage movement tracking

#### 5.4 ECL Journal Service
- ECL allowance journal posting
- Write-off handling
- Allowance movement tracking

#### 5.5 Deferred Tax Integration
- `investment_deferred_tax` table
- Deferred tax calculation
- Integration with tax module

#### 5.6 Reporting
- Portfolio Summary Report
- Maturity Ladder Report
- ECL Movement Report
- IFRS 9 Classification Report
- Audit Pack Generator

#### 5.7 Frontend Screens
- ECL Calculation Run Screen
- Exposure Drilldown
- Model Parameter Management
- Scenario Management
- ECL Reports Dashboard
- Audit Pack Export

#### 5.8 Scheduled Jobs
- Monthly ECL calculation job
- Backtesting job (optional)

### Acceptance Criteria
- ✅ ECL calculated correctly for sample data
- ✅ Staging rules work (Stage 1 → 2 → 3)
- ✅ ECL journals post correctly
- ✅ Provision matrix works for receivables
- ✅ Scenario weighting produces expected results
- ✅ Audit pack exports all required data
- ✅ Reports match manual calculations

### Technical Notes
- Store all calculation inputs for reproducibility
- Implement model versioning
- Approval required for ECL overrides
- Backtesting framework for model validation

---

## Technical Architecture Decisions

### 1. Calculation Precision
- Use `bcmath` or `Decimal` library for financial calculations
- Store monetary values as `DECIMAL(18,2)` in database
- Round only at final display/posting stage

### 2. EIR Solver Algorithm
- Start with bisection method (simpler, more stable)
- Consider Newton-Raphson for performance if needed
- Tolerance: 1e-8
- Max iterations: 100

### 3. Journal Posting Strategy
- Always preview before posting
- Store journal preview in database
- Require approval for large amounts (configurable)
- Use existing `Journal` model structure

### 4. Scheduled Jobs
- Use Laravel's task scheduler
- Implement idempotency using job run tokens
- Use database locks to prevent concurrent execution
- Log all job runs for audit

### 5. Caching Strategy
- Cache EIR calculations (invalidate on cash flow changes)
- Cache model parameters (invalidate on updates)
- Cache portfolio summaries (TTL: 5 minutes)

### 6. API Design
- RESTful endpoints
- Use Laravel API Resources for responses
- Implement rate limiting
- Version API (v1)

---

## Database Schema Summary

### Core Investment Tables
1. `investment_master` - Main investment records
2. `investment_trade` - Trade transactions
3. `investment_proposals` - Investment proposals
4. `investment_approvals` - Approval history
5. `investment_amort_line` - EIR amortization schedule
6. `investment_valuation` - Valuation history
7. `investment_ecl` - ECL calculations (if separate from general ECL)
8. `investment_deferred_tax` - Deferred tax tracking

### ECL Tables
1. `ecl_model_params` - PD/LGD/EAD parameters
2. `ecl_scenarios` - Forward-looking scenarios
3. `ecl_inputs` - Exposure snapshots
4. `ecl_calc` - ECL calculation results
5. `ecl_override` - Manual overrides

### Supporting Tables
1. `investment_attachments` - File attachments
2. `investment_audit_log` - Immutable audit trail
3. `market_prices` - Market price history

---

## Security & Compliance

### RBAC Roles
- `treasury_user` - Create proposals, capture trades
- `treasury_manager` - Approve proposals, post journals
- `cfo` - Final approval for large amounts
- `valuation_analyst` - Enter valuations
- `auditor_readonly` - View-only access

### Segregation of Duties
- Maker ≠ Checker (enforced at workflow level)
- Valuation entry ≠ Approval (separate roles)
- ECL override requires dual approval

### Audit Requirements
- Immutable audit log for all critical actions
- Store calculation inputs for reproducibility
- Version control for model parameters
- Export capability for auditors

---

## Testing Strategy

### Unit Tests
- EIR solver accuracy
- Day count conventions
- Journal generation logic
- ECL calculation formulas

### Integration Tests
- End-to-end workflows
- GL posting integration
- Approval workflow
- Scheduled jobs

### Acceptance Tests
- Sample data validation
- Report accuracy
- Audit pack completeness
- Performance benchmarks

---

## Risk Mitigation

### Technical Risks
1. **EIR Calculation Accuracy**
   - Mitigation: Extensive unit tests, compare with Excel/industry tools
   
2. **Performance with Large Portfolios**
   - Mitigation: Chunk processing, background jobs, caching

3. **Data Integrity**
   - Mitigation: Database constraints, transaction wrapping, validation

### Business Risks
1. **Regulatory Compliance**
   - Mitigation: Regular review with finance team, audit trail

2. **User Adoption**
   - Mitigation: Training, documentation, phased rollout

---

## Dependencies & Prerequisites

### External Dependencies
- Market data feed (BOT/DSE) - Phase 4
- Bank/RTGS integration - Phase 2
- Tax module integration - Phase 5

### Internal Dependencies
- Chart of Accounts setup
- Approval workflow configuration
- Period closing module (for period locking)

---

## Success Metrics

### Phase 1
- 100% of proposals go through approval workflow
- Zero data loss in audit logs

### Phase 2
- 100% of trades generate correct journals
- Settlement workflow completes in < 5 minutes

### Phase 3
- EIR calculations within 0.01% of manual calculations
- Amortization schedules match Excel models

### Phase 4
- Valuation journals post correctly
- Fair value hierarchy report complete

### Phase 5
- ECL calculations match manual calculations (within tolerance)
- Audit pack exports all required data
- Reports generate in < 30 seconds

---

## Next Steps

1. **Review & Approval**: Stakeholder review of this plan
2. **Phase 1 Kickoff**: Begin database design and model creation
3. **Resource Allocation**: Assign developers, QA, BA
4. **Environment Setup**: Dev, staging environments
5. **Weekly Progress Reviews**: Track progress against plan

---

## Appendix: Sample Data for Testing

### Test Instruments
1. T-Bill: 100,000 TZS, 91 days, zero coupon
2. T-Bond: 1,000,000 TZS, 5 years, 10% semi-annual coupon
3. Fixed Deposit: 500,000 TZS, 1 year, 12% annual
4. Corporate Bond: 2,000,000 TZS, 3 years, 8% quarterly, at premium
5. Equity: 100 shares, quoted price

### Test Scenarios
- Purchase at par
- Purchase at premium
- Purchase at discount
- Early disposal
- Maturity
- Impairment (Stage 1 → 2 → 3)

---

**Document Version**: 1.0  
**Last Updated**: 2025-01-XX  
**Author**: Development Team  
**Status**: Draft - Awaiting Approval

