const express = require("express");
const cors = require("cors");
const { ethers } = require("ethers");

const app = express();
app.use(cors());
app.use(express.json());

app.post("/verify", (req, res) => {
  const { address, signature, message } = req.body;

  if (!address || !signature || !message) {
    return res.status(400).json({ success: false, error: "Missing fields" });
  }

  try {
    const recovered = ethers.verifyMessage(message, signature);

    if (recovered.toLowerCase() !== address.toLowerCase()) {
      return res.json({ success: false, error: "Invalid signature" });
    }

    return res.json({ success: true });

  } catch (err) {
    console.error(err);
    return res.status(500).json({ success: false, error: "Verification failed" });
  }
});

app.listen(3000, () => {
  console.log("✅ Verifier running on http://localhost:3000");
});