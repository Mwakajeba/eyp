# Fleet Tyres & Spare Parts – Implementation Plan

This document summarizes the **recommended accounting and ERP flow** for tyres and spare parts (Vipuri) and the changes to implement in SmartAccounting.

## 1. Initial Truck Purchase (with tyres included)

- **Keep** the truck with its tyres as PPE (one fixed asset).
- **Track** initial tyres in Tyre Master **for control only** – no separate financial posting to PPE.
- **Accounting:** DR Motor Vehicles (full truck cost including tyres), CR AP/Cash.
- Tyre Master records for the truck should appear as **Inventory** type (status: on_vehicle / control_only) so that when you purchase a **replacement** tyre it follows the same inventory → expense flow.

## 2. Tyre Replacements (after truck is in service)

| Step | Action | Accounting |
|------|--------|------------|
| Purchase | Record tyre as **Inventory** in ERP | DR Tyre Inventory, CR AP/Cash |
| Master data | DOT number, brand, size, warranty, lifespan km, supplier, purchase cost. **Prevent duplicate DOT numbers.** | — |
| Installation | When tyre is installed on truck | DR Truck/Maintenance Expense, CR Tyre Inventory |
| Link | Truck ID, Trip ID (if applicable), Installation Date. **Approval workflow:** e.g. Logistics Officer → Director. | — |

**ERP controls to implement:**

- Prevent installation of tyres replaced too recently (cool-down / reasonableness).
- Track each tyre by DOT number, serial, truck, installation date.
- Limit reasonable number of tyres replaced per truck at a time.
- Apply the **same logic for Vipuri (other spare parts)** where applicable.

## 3. Benefits of the corrected flow

| Aspect | Old (tyres as PPE) | New (inventory → expense) |
|--------|--------------------|----------------------------|
| Accounting | Overstates assets | Correctly expenses consumables |
| Depreciation | Complicated | Only truck depreciates |
| Control | Hard to track replacements | Serial/DOT, installation date, approval |
| Auditors | May flag PPE misstatement | Aligns with IAS 16 and matching principle |
| Profitability | Distorted truck cost | Expense when used → accurate P&L per truck/trip |

## 4. ERP / SmartAccounting implementation notes

- **Tyre Master table:** DOT/serial, brand, size, warranty km/months, purchase date, cost, **status** (Inventory / Installed / Retired). Treat as **inventory** for replacement tyres; initial truck tyres can be “control only” with no separate asset posting.
- **Truck/Vehicle Master:** Link installed tyres to truck (and optionally trip).
- **Approval workflow:** Required for installation (e.g. Logistics Officer → Director) to prevent misuse.
- **Reports & dashboards:** Tyres installed per truck; tyres in inventory vs installed; cost per truck/trip; warranty alerts (km or months); replacement frequency per truck.

## 5. End-to-end flow (textual)

1. Truck purchase (with tyres) → Register truck in PPE (full cost).
2. Register initial tyres in Tyre Master (control only, no separate PPE posting).
3. Truck in service.
4. Tyre replacement required → Purchase replacement tyres → **Tyre Inventory**.
5. Installation → **Maintenance/Truck Expense** (DR Expense, CR Tyre Inventory).
6. Approval workflow (e.g. Logistics Officer → Director).
7. Update truck/trip cost and dashboard/reports (tyre cost, replacements, warranties).

## 6. Summary of changes to implement

1. **Do not** post initial tyres as PPE separately; track them in the system for control only.
2. **Replacement tyres:** post to **Inventory**, then **Expense on installation**.
3. Implement **approval workflow** for all tyre (and optionally spare) installations.
4. **Track DOT/Serial** and prevent duplicate DOTs; enforce reasonable replacement frequency.
5. **Link costs** to truck and trips for accurate profitability reporting.

The existing Fleet module already has **FleetTyre**, **FleetTyreInstallation**, **FleetTyreReplacementRequest**, and **FleetApprovalWorkflow**. Next steps are: (1) ensure Tyre Master supports “inventory” status and is not posted as PPE; (2) add approval workflow to installations; (3) enforce DOT uniqueness and cool-down rules; (4) extend the same pattern to Vipuri where required.
