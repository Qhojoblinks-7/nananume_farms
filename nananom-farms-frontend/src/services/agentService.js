// src/services/agentService.js
import { get, put, del } from './api';

// Admin - Get all support agents
export const getAllAgents = async (filters = {}) => {
  try {
    const queryParams = new URLSearchParams();
    
    if (filters.is_active !== undefined) queryParams.append('is_active', filters.is_active);
    if (filters.region) queryParams.append('region', filters.region);
    if (filters.page) queryParams.append('page', filters.page);
    if (filters.limit) queryParams.append('limit', filters.limit);
    
    const endpoint = `/api/agents${queryParams.toString() ? `?${queryParams.toString()}` : ''}`;
    const data = await get(endpoint);
    return data;
  } catch (error) {
    console.error('Error fetching all agents:', error.message);
    throw error;
  }
};

// Admin - Update agent details
export const updateAgent = async (agentId, agentData) => {
  try {
    const data = await put('/api/agents', {
      agent_id: agentId,
      ...agentData
    });
    return data;
  } catch (error) {
    console.error('Error updating agent:', error.message);
    throw error;
  }
};

// Admin - Delete agent
export const deleteAgent = async (agentId) => {
  try {
    const data = await del('/api/agents', {
      body: JSON.stringify({ agent_id: agentId })
    });
    return data;
  } catch (error) {
    console.error('Error deleting agent:', error.message);
    throw error;
  }
};

// Get agent statistics for dashboard
export const getAgentStats = async () => {
  try {
    const data = await get('/api/agents?limit=1000'); // Get all for stats
    const agents = data.agents || [];
    
    const stats = {
      total: agents.length,
      active: agents.filter(a => a.is_active).length,
      inactive: agents.filter(a => !a.is_active).length,
      byRegion: {}
    };
    
    // Group by region
    agents.forEach(agent => {
      const region = agent.region || 'Unassigned';
      if (!stats.byRegion[region]) {
        stats.byRegion[region] = 0;
      }
      stats.byRegion[region]++;
    });
    
    return stats;
  } catch (error) {
    console.error('Error fetching agent stats:', error.message);
    throw error;
  }
};