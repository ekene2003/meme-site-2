document.addEventListener("DOMContentLoaded", function () {
    const top_bar_username = document.querySelector("#top_bar_username");
    const sb_bar_username = document.querySelector("#sb-username");
    const sb_bar_wallet = document.querySelector("#sb-wallet");

    const API_URL = "./server/user_details.php"; 

  async function fetchAndUpdateUI() {
        try {
            const response = await fetch(API_URL);
            const data = await response.json();
            
            // Check if the data has the field you need (e.g., 'username')
            if (data) {
                top_bar_username.textContent = data.username;
                sb_bar_username.textContent = data.username;

                if(data.wallet){
                     
                    var wallet_view = data.wallet.substr(0,5)+'...'+data.wallet.slice(-5);
                    // console.log(wallet_view);
                    sb_bar_wallet.textContent = wallet_view;
                }else if(data.email){
                      sb_bar_wallet.textContent = data.email;
                }
            } else if (data.error) {
                console.warn("Server returned error:", data.error);
            }
         
        } catch (error) {
            console.error("Error fetching data:", error);
        }
    }

    // Fetch data initially
    fetchAndUpdateUI();
    setInterval(fetchAndUpdateUI, 5 * 60 * 1000);
});
