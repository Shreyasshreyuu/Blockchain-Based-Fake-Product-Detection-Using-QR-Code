# Blockchain Based Fake Product Detection Using QR-Code
# Local Real Blockchain Setup

This project now supports a real local blockchain using a Solidity smart contract deployed to a local Hardhat network. For a college project, portfolio build, or zero-cost demo, this is the best free option because it gives you real smart-contract transactions without gas fees, faucet delays, or paid RPC services.

## One-time install

```bash
npm install
```

## Fastest startup option

Use one command in the project folder:

```bash
npm run blockchain:start
```

This command:

- starts the local Hardhat blockchain node if it is not already running
- compiles the smart contract
- deploys the contract to the current local chain
- starts the blockchain API bridge if it is not already running

If the node or API is already running, the command reuses them instead of starting duplicates.

## Manual startup flow

Use three terminals in the project folder.

### Terminal 1: start the blockchain node

```bash
npm run blockchain:node
```

Keep this terminal open while using the app.

### Terminal 2: compile and deploy the contract

```bash
npm run blockchain:compile
npm run blockchain:deploy
```

The compile step is fully offline and uses the local `solc` package already installed in the project.

The deploy step writes the live contract address to:

`blockchain/deployments/localhost.json`

### Terminal 3: start the blockchain bridge API

```bash
npm run blockchain:api
```

The PHP app talks to this bridge at:

`http://127.0.0.1:3001/api/blockchain`

## What the app does

- Product creation writes a real transaction to the smart contract.
- Product verification writes a real transaction to the smart contract.
- The MySQL ledger remains in place as a fast local cache and UI history store.
- Dashboard and scanner surfaces show whether the real blockchain is active.
- If the blockchain services are not running, the app can fall back to the old simulated ledger mode.

## Quick health check

When everything is running:

- `dashboard.php` shows `Real blockchain active`
- `scan-qr.php` shows `Real blockchain active`
- `blockchain/deployments/localhost.json` contains the contract address

## Important note

This is a real blockchain, but it is local to your machine. That means:

- It is perfect for free demos, viva presentations, and project submissions.
- It resets if you restart the local chain and redeploy.
- If you want public on-chain verification later, the next upgrade path would be Polygon Amoy or Ethereum Sepolia.
